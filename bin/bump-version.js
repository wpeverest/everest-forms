#!/usr/bin/env node
/**
 * Bump Everest Forms' own version number and drop a changelog placeholder
 * in place. Usage:
 *   node bin/bump-version.js
 */
const fs = require('fs');
const path = require('path');
const readline = require('readline');

const ROOT = path.join(__dirname, '..');
const mainFile = path.join(ROOT, 'everest-forms.php');
const classFile = path.join(ROOT, 'includes', 'class-everest-forms.php');
const readmeFile = path.join(ROOT, 'readme.txt');
const changelogFile = path.join(ROOT, 'CHANGELOG.txt');
const packageJsonFile = path.join(ROOT, 'package.json');

const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
function ask(question) {
	return new Promise((resolve) => rl.question(question, (answer) => resolve(answer.trim())));
}

function escapeRegex(s) {
	return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function readVersion(filePath) {
	const content = fs.readFileSync(filePath, 'utf8');
	const match = content.match(/\*\s*Version:\s*([\d.]+)/);
	return match ? match[1] : null;
}

function bumpMainFile(filePath, oldVersion, newVersion) {
	let content = fs.readFileSync(filePath, 'utf8');
	content = content.replace(
		new RegExp(`(\\*\\s*Version:\\s*)${escapeRegex(oldVersion)}`),
		`$1${newVersion}`,
	);
	content = content.replace(
		new RegExp(`(define\\(\\s*'[A-Z_]*VERSION'\\s*,\\s*')${escapeRegex(oldVersion)}('\\s*\\))`),
		`$1${newVersion}$2`,
	);
	fs.writeFileSync(filePath, content);
}

function bumpClassVersionProperty(filePath, newVersion) {
	if (!fs.existsSync(filePath)) return;
	let content = fs.readFileSync(filePath, 'utf8');
	content = content.replace(/public \$version = '.*?';/, `public $version = '${newVersion}';`);
	fs.writeFileSync(filePath, content);
}

function bumpReadmeStableTag(filePath, newVersion) {
	if (!fs.existsSync(filePath)) return;
	let content = fs.readFileSync(filePath, 'utf8');
	content = content.replace(/Stable tag:\s*[\d.]+/, `Stable tag: ${newVersion}`);
	fs.writeFileSync(filePath, content);
}

function insertChangelogPlaceholder(filePath, newVersion) {
	if (!fs.existsSync(filePath)) return;
	let content = fs.readFileSync(filePath, 'utf8');
	const placeholder = `= ${newVersion}${' '.repeat(7)}- xx-xx-2026 =\n* \n\n`;
	content = content.replace(/(==\s*Changelog\s*==\s*\n+)/, `$1${placeholder}`);
	fs.writeFileSync(filePath, content);
}

function bumpPackageJsonVersion(filePath, newVersion) {
	if (!fs.existsSync(filePath)) return;
	const pkg = JSON.parse(fs.readFileSync(filePath, 'utf8'));
	if (typeof pkg.version !== 'string') return;
	pkg.version = newVersion;
	fs.writeFileSync(filePath, JSON.stringify(pkg, null, 2) + '\n');
}

(async () => {
	const currentVersion = readVersion(mainFile);
	console.log(`Current version: ${currentVersion}`);
	const newVersion = await ask('New version: ');

	if (!/^\d+\.\d+(\.\d+)*$/.test(newVersion)) {
		console.error('That doesn\'t look like a version number (expected e.g. 3.6.1).');
		process.exit(1);
	}

	bumpMainFile(mainFile, currentVersion, newVersion);
	bumpClassVersionProperty(classFile, newVersion);
	bumpReadmeStableTag(readmeFile, newVersion);
	insertChangelogPlaceholder(readmeFile, newVersion);
	insertChangelogPlaceholder(changelogFile, newVersion);
	bumpPackageJsonVersion(packageJsonFile, newVersion);

	console.log(`\nDone: ${currentVersion} -> ${newVersion}`);
	console.log('Now fill in the changelog bullet(s) (look for "xx-xx-2026") in:');
	console.log(`  ${readmeFile}`);
	if (fs.existsSync(changelogFile)) console.log(`  ${changelogFile}`);
	rl.close();
})();
