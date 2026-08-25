#!/bin/bash

# Note that this does not use pipefail
# because if the grep later doesn't match any deleted files,
# which is likely to be the case the majority of the time,
# it does not exit with 0, as we are interested in the final exit.
set -eo

# Function to check if a command exists
command_exists() {
	command -v "$1" >/dev/null 2>&1
}

# Check if SVN is installed
if command_exists svn; then
	echo "SVN is already installed."
else
	echo "SVN is not installed. Installing SVN..."

	# Update the package list
	sudo apt-get update -y

	# Install SVN
	sudo apt-get install -y subversion

	# Verify installation
	if command_exists svn; then
		echo "SVN was successfully installed."
	else
		echo "Failed to install SVN. Please check your system configuration."
		exit 1
	fi
fi

# Ensure SVN username and password are set
# IMPORTANT: while secrets are encrypted and not viewable in the GitHub UI,
# they are by necessity provided as plaintext in the context of the Action,
# so do not echo or use debug mode unless you want your secrets exposed!

# Check if it's a dry-run first
if $INPUT_DRY_RUN; then
  echo "ℹ︎ Dry run: No files will be committed to Subversion."

  if [[ -z "$SVN_USERNAME" ]]; then
    echo "Warning: SVN_USERNAME is missing. The commit will fail if you attempt a real run."
  fi

  if [[ -z "$SVN_PASSWORD" ]]; then
    echo "Warning: SVN_PASSWORD is missing. The commit will fail if you attempt a real run."
  fi
else
  # If it's not a dry-run, check for SVN credentials
  if [[ -z "$SVN_USERNAME" ]]; then
    echo "Set the SVN_USERNAME secret"
    exit 1
  fi

  if [[ -z "$SVN_PASSWORD" ]]; then
    echo "Set the SVN_PASSWORD secret"
    exit 1
  fi
fi

echo "ℹ︎ If we got here without exiting, the dry-run check worked correctly."
