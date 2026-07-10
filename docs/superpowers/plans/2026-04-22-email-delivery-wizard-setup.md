# Email Delivery Wizard Setup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** After a test email or form submission email is sent via `wp_mail()`, the Site Assistant wizard's "Send Test Email" step shows an inline red/green status banner and — when Smart SMTP is not installed — a Smart SMTP install card with a one-click inline install button.

**Architecture:** PHP REST API (`EVF_Site_Assistant`) is extended to return Smart SMTP plugin state and the last form-submission email result on every response. `EVF_Emails::send()` writes a WP option on every send attempt. The React `SiteAssistant` component reads this data and drives all UI state from `onSuccess` (the test-email endpoint always returns HTTP 200, never `WP_Error`). The Smart SMTP install button calls `admin-ajax.php` using the existing `install_and_activate_smart_smtp` AJAX action.

**Tech Stack:** PHP 7.4+ / WordPress REST API, React + TypeScript, Chakra UI, `@tanstack/react-query`, `@wordpress/api-fetch`, native `fetch` for admin-ajax calls, PHPUnit / WP_UnitTestCase.

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `includes/RestApi/controllers/version1/class-evf-site-assistant.php` | Modify | Add Smart SMTP helpers; extend GET & test-email responses; change failure to 200 JSON |
| `includes/class-evf-emails.php` | Modify | Write `everest_forms_last_form_email_status` WP option on every send |
| `includes/admin/class-evf-admin-dashboard.php` | Modify | Add `smartSmtpNonce` and `ajaxURL` to `_EVF_DASHBOARD_` |
| `src/dashboard/screens/SiteAssistant/SiteAssistant.tsx` | Modify | Add `emailStatus` state, status banner, Smart SMTP install card |
| `tests/phpunit/includes/class-evf-site-assistant-test.php` | Create | PHPUnit tests for new API response fields and email status tracking |

---

### Task 1: Write failing PHPUnit tests for Smart SMTP helpers and GET response fields

**Files:**
- Create: `tests/phpunit/includes/class-evf-site-assistant-test.php`

- [ ] **Step 1: Create the test file**

```php
<?php
/**
 * Tests for EVF_Site_Assistant REST API extensions.
 */
class EVF_Site_Assistant_Test extends WP_UnitTestCase {

    protected $assistant;

    public function setUp(): void {
        parent::setUp();
        require_once EVF_ABSPATH . 'includes/RestApi/controllers/version1/class-evf-site-assistant.php';
        $this->assistant = new EVF_Site_Assistant();
    }

    public function tearDown(): void {
        parent::tearDown();
        delete_option( 'active_plugins' );
        delete_option( 'everest_forms_last_form_email_status' );
    }

    /** Smart SMTP not in active_plugins → is_smart_smtp_active returns false */
    public function test_is_smart_smtp_active_returns_false_when_not_active() {
        update_option( 'active_plugins', array() );
        $result = $this->call_protected( 'is_smart_smtp_active' );
        $this->assertFalse( $result );
    }

    /** Smart SMTP in active_plugins → is_smart_smtp_active returns true */
    public function test_is_smart_smtp_active_returns_true_when_active() {
        update_option( 'active_plugins', array( 'smart-smtp/smart-smtp.php' ) );
        $result = $this->call_protected( 'is_smart_smtp_active' );
        $this->assertTrue( $result );
    }

    /** GET response includes last_form_email_status from WP option */
    public function test_get_status_includes_last_form_email_status() {
        update_option( 'everest_forms_last_form_email_status', 'failed' );
        wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
        $request  = new WP_REST_Request( 'GET', '/everest-forms/v1/site-assistant' );
        $response = $this->assistant->get_status( $request );
        $data     = $response->get_data();
        $this->assertSame( 'failed', $data['data']['last_form_email_status'] );
    }

    /** GET response includes is_smart_smtp_installed as boolean */
    public function test_get_status_includes_is_smart_smtp_installed() {
        wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
        $request  = new WP_REST_Request( 'GET', '/everest-forms/v1/site-assistant' );
        $response = $this->assistant->get_status( $request );
        $data     = $response->get_data();
        $this->assertArrayHasKey( 'is_smart_smtp_installed', $data['data'] );
        $this->assertIsBool( $data['data']['is_smart_smtp_installed'] );
    }

    /** GET response includes is_smart_smtp_active as boolean */
    public function test_get_status_includes_is_smart_smtp_active() {
        wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
        $request  = new WP_REST_Request( 'GET', '/everest-forms/v1/site-assistant' );
        $response = $this->assistant->get_status( $request );
        $data     = $response->get_data();
        $this->assertArrayHasKey( 'is_smart_smtp_active', $data['data'] );
        $this->assertIsBool( $data['data']['is_smart_smtp_active'] );
    }

    /** test-email endpoint returns HTTP 200 with email_sent=false when wp_mail returns false */
    public function test_send_test_email_returns_200_on_mail_failure() {
        add_filter( 'pre_wp_mail', '__return_false' );
        wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
        $request = new WP_REST_Request( 'POST', '/everest-forms/v1/site-assistant/test-email' );
        $request->set_param( 'email', 'test@example.com' );
        $response = $this->assistant->send_test_email( $request );
        remove_filter( 'pre_wp_mail', '__return_false' );
        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $data = $response->get_data();
        $this->assertTrue( $data['success'] );
        $this->assertFalse( $data['data']['email_sent'] );
    }

    /** test-email endpoint returns email_sent=true on success */
    public function test_send_test_email_returns_email_sent_true_on_success() {
        add_filter( 'pre_wp_mail', '__return_true' );
        wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
        $request = new WP_REST_Request( 'POST', '/everest-forms/v1/site-assistant/test-email' );
        $request->set_param( 'email', 'test@example.com' );
        $response = $this->assistant->send_test_email( $request );
        remove_filter( 'pre_wp_mail', '__return_true' );
        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $data = $response->get_data();
        $this->assertTrue( $data['data']['email_sent'] );
    }

    /** Helper to call protected methods */
    private function call_protected( string $method, array $args = [] ) {
        $ref = new ReflectionMethod( EVF_Site_Assistant::class, $method );
        $ref->setAccessible( true );
        return $ref->invokeArgs( $this->assistant, $args );
    }
}
```

- [ ] **Step 2: Run tests to verify they all fail**

```bash
cd C:/laragon/www/evf/wp-content/plugins/everest-forms
vendor/bin/phpunit tests/phpunit/includes/class-evf-site-assistant-test.php --verbose
```

Expected: all tests FAIL — methods and fields don't exist yet.

---

### Task 2: Add Smart SMTP helper methods to EVF_Site_Assistant

**Files:**
- Modify: `includes/RestApi/controllers/version1/class-evf-site-assistant.php`

- [ ] **Step 1: Add `is_smart_smtp_installed()` and `is_smart_smtp_active()` methods**

Open `includes/RestApi/controllers/version1/class-evf-site-assistant.php`. After the `is_recaptcha_keys_set()` method (around line 170), add:

```php
/**
 * Check if Smart SMTP plugin is installed (not necessarily active).
 *
 * @return bool
 */
protected function is_smart_smtp_installed() {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    return isset( get_plugins()['smart-smtp/smart-smtp.php'] );
}

/**
 * Check if Smart SMTP plugin is active.
 *
 * @return bool
 */
protected function is_smart_smtp_active() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    return is_plugin_active( 'smart-smtp/smart-smtp.php' );
}
```

- [ ] **Step 2: Run only the helper-related tests to verify they now pass**

```bash
vendor/bin/phpunit tests/phpunit/includes/class-evf-site-assistant-test.php --filter "test_is_smart_smtp" --verbose
```

Expected: 2 tests PASS.

---

### Task 3: Extend GET /site-assistant response with new fields

**Files:**
- Modify: `includes/RestApi/controllers/version1/class-evf-site-assistant.php`

- [ ] **Step 1: Update `get_status()` to include new fields**

In `get_status()`, find `$response_data = array(` (around line 210) and replace the array with:

```php
$response_data = array(
    'skipped_steps'              => $skipped_steps,
    'test_email_sent'            => $test_email_sent,
    'spam_protection_configured' => $this->is_spam_protection_configured(),
    'all_steps_completed'        => $this->are_all_steps_completed(),
    'has_forms'                  => $this->has_forms(),
    'last_form_email_status'     => get_option( 'everest_forms_last_form_email_status', '' ),
    'is_smart_smtp_installed'    => $this->is_smart_smtp_installed(),
    'is_smart_smtp_active'       => $this->is_smart_smtp_active(),
);
```

- [ ] **Step 2: Update `skip_setup()` response data array**

In `skip_setup()`, find the final `return rest_ensure_response(` block. Replace the inner `'data'` array with:

```php
'data' => array(
    'skipped_steps'              => $skipped_steps,
    'test_email_sent'            => $test_email_sent,
    'spam_protection_configured' => $this->is_spam_protection_configured(),
    'all_steps_completed'        => $this->are_all_steps_completed(),
    'has_forms'                  => $this->has_forms(),
    'last_form_email_status'     => get_option( 'everest_forms_last_form_email_status', '' ),
    'is_smart_smtp_installed'    => $this->is_smart_smtp_installed(),
    'is_smart_smtp_active'       => $this->is_smart_smtp_active(),
),
```

- [ ] **Step 3: Run the GET response tests**

```bash
vendor/bin/phpunit tests/phpunit/includes/class-evf-site-assistant-test.php --filter "test_get_status" --verbose
```

Expected: 3 tests PASS.

---

### Task 4: Change test-email endpoint to always return HTTP 200

**Files:**
- Modify: `includes/RestApi/controllers/version1/class-evf-site-assistant.php`

- [ ] **Step 1: Update `send_test_email()` success branch to include new fields and `email_sent: true`**

In `send_test_email()`, find the `if ( $email_sent )` block return statement and replace the returned data array:

```php
return rest_ensure_response(
    array(
        'success' => true,
        'message' => __( 'Test email sent successfully. Didn\'t receive it? Please check your Spam or Junk folder.', 'everest-forms' ),
        'data'    => array(
            'email_sent'              => true,
            'test_email_sent'         => true,
            'skipped_steps'           => $skipped_steps,
            'spam_protection_configured' => $this->is_spam_protection_configured(),
            'all_steps_completed'     => $this->are_all_steps_completed(),
            'has_forms'               => $this->has_forms(),
            'last_form_email_status'  => get_option( 'everest_forms_last_form_email_status', '' ),
            'is_smart_smtp_installed' => $this->is_smart_smtp_installed(),
            'is_smart_smtp_active'    => $this->is_smart_smtp_active(),
        ),
    )
);
```

- [ ] **Step 2: Replace the `else` branch (currently `WP_Error`) with a 200 JSON response**

Find and replace the entire `else { return new \WP_Error( 'email_send_failed', ... ); }` block with:

```php
} else {
    $skipped_steps           = array();
    $spam_protection_skipped = $this->is_spam_protection_completed();
    $create_form_skipped     = (bool) get_option( self::CREATE_FORM_SKIPPED, false );

    if ( $spam_protection_skipped ) {
        $skipped_steps[] = 'spam_protection';
    }
    if ( $create_form_skipped ) {
        $skipped_steps[] = 'create_form';
    }

    return rest_ensure_response(
        array(
            'success' => true,
            'data'    => array(
                'email_sent'              => false,
                'test_email_sent'         => false,
                'skipped_steps'           => $skipped_steps,
                'spam_protection_configured' => $this->is_spam_protection_configured(),
                'all_steps_completed'     => $this->are_all_steps_completed(),
                'has_forms'               => $this->has_forms(),
                'last_form_email_status'  => get_option( 'everest_forms_last_form_email_status', '' ),
                'is_smart_smtp_installed' => $this->is_smart_smtp_installed(),
                'is_smart_smtp_active'    => $this->is_smart_smtp_active(),
            ),
        )
    );
}
```

- [ ] **Step 3: Run all site-assistant tests**

```bash
vendor/bin/phpunit tests/phpunit/includes/class-evf-site-assistant-test.php --verbose
```

Expected: all 7 tests PASS.

- [ ] **Step 4: Commit**

```bash
git add includes/RestApi/controllers/version1/class-evf-site-assistant.php tests/phpunit/includes/class-evf-site-assistant-test.php
git commit -m "feat: extend site-assistant API with Smart SMTP status and email delivery result"
```

---

### Task 5: Track form email status in EVF_Emails

**Files:**
- Modify: `includes/class-evf-emails.php`

- [ ] **Step 1: Write a failing test for the WP option**

Add this test to `tests/phpunit/includes/class-evf-site-assistant-test.php` inside the class:

```php
/** EVF_Emails::send() writes 'failed' to WP option when wp_mail returns false */
public function test_emails_send_writes_failed_status_on_mail_failure() {
    add_filter( 'pre_wp_mail', '__return_false' );
    delete_option( 'everest_forms_last_form_email_status' );

    $emails            = new EVF_Emails();
    $emails->form_data = array(
        'id'       => 1,
        'settings' => array( 'email' => array() ),
    );
    $emails->fields   = array();
    $emails->entry_id = 0;
    @$emails->send( 'test@example.com', 'Subject', 'Body' );

    remove_filter( 'pre_wp_mail', '__return_false' );
    $this->assertSame( 'failed', get_option( 'everest_forms_last_form_email_status' ) );
}

/** EVF_Emails::send() writes 'success' to WP option when wp_mail returns true */
public function test_emails_send_writes_success_status_on_mail_success() {
    add_filter( 'pre_wp_mail', '__return_true' );
    delete_option( 'everest_forms_last_form_email_status' );

    $emails            = new EVF_Emails();
    $emails->form_data = array(
        'id'       => 1,
        'settings' => array( 'email' => array() ),
    );
    $emails->fields   = array();
    $emails->entry_id = 0;
    @$emails->send( 'test@example.com', 'Subject', 'Body' );

    remove_filter( 'pre_wp_mail', '__return_true' );
    $this->assertSame( 'success', get_option( 'everest_forms_last_form_email_status' ) );
}
```

- [ ] **Step 2: Run to verify the new tests fail**

```bash
vendor/bin/phpunit tests/phpunit/includes/class-evf-site-assistant-test.php --filter "test_emails_send" --verbose
```

Expected: both FAIL.

- [ ] **Step 3: Add the WP option write to `EVF_Emails::send()`**

Open `includes/class-evf-emails.php`. Find the line `return $sent;` at the end of `send()` (around line 379). Just before it, add:

```php
update_option( 'everest_forms_last_form_email_status', $sent ? 'success' : 'failed' );
```

The block now reads:
```php
// Hooks after the email is sent.
do_action( 'everest_forms_email_send_after', $this );

update_option( 'everest_forms_last_form_email_status', $sent ? 'success' : 'failed' );

return $sent;
```

- [ ] **Step 4: Run to verify the tests now pass**

```bash
vendor/bin/phpunit tests/phpunit/includes/class-evf-site-assistant-test.php --filter "test_emails_send" --verbose
```

Expected: both PASS.

- [ ] **Step 5: Run full test suite to check for regressions**

```bash
vendor/bin/phpunit tests/phpunit/includes/class-evf-site-assistant-test.php --verbose
```

Expected: all 9 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add includes/class-evf-emails.php tests/phpunit/includes/class-evf-site-assistant-test.php
git commit -m "feat: track form email delivery status in WP option on every send"
```

---

### Task 6: Add smartSmtpNonce and ajaxURL to _EVF_DASHBOARD_

**Files:**
- Modify: `includes/admin/class-evf-admin-dashboard.php`

- [ ] **Step 1: Add the two fields to the `wp_localize_script` call**

Open `includes/admin/class-evf-admin-dashboard.php`. Find the `'_EVF_DASHBOARD_'` `wp_localize_script` array (around line 95). Add after `'evfRestApiNonce'`:

```php
'ajaxURL'              => esc_url( admin_url( 'admin-ajax.php' ) ),
'smartSmtpNonce'       => wp_create_nonce( 'everest-forms-smart-smtp-installation-nonce' ),
```

- [ ] **Step 2: Verify by loading the EVF dashboard page in the browser**

Navigate to `wp-admin → Everest Forms`. Open browser DevTools → Console and run:

```js
console.log(window._EVF_DASHBOARD_.ajaxURL, window._EVF_DASHBOARD_.smartSmtpNonce)
```

Expected: prints the admin-ajax.php URL and a nonce string (not `undefined`).

- [ ] **Step 3: Commit**

```bash
git add includes/admin/class-evf-admin-dashboard.php
git commit -m "feat: add smartSmtpNonce and ajaxURL to _EVF_DASHBOARD_ for inline Smart SMTP install"
```

---

### Task 7: Update TypeScript interface and add emailStatus state

**Files:**
- Modify: `src/dashboard/screens/SiteAssistant/SiteAssistant.tsx`

- [ ] **Step 1: Update `SiteAssistantData` interface**

Find the `SiteAssistantData` interface (around line 60) and replace it with:

```typescript
interface SiteAssistantData {
  skipped_steps: string[];
  test_email_sent: boolean;
  has_forms: boolean;
  email_sent?: boolean;           // present only in test-email endpoint responses
  last_form_email_status: 'success' | 'failed' | '';
  is_smart_smtp_installed: boolean;
  is_smart_smtp_active: boolean;
}
```

- [ ] **Step 2: Destructure new dashboard globals**

Find the destructuring line (around line 85):
```typescript
const { utmCampaign, evfRestApiNonce, restURL, adminEmail, adminURL, isPro } = dashboardData;
```

Replace with:
```typescript
const { utmCampaign, evfRestApiNonce, restURL, adminEmail, adminURL, isPro, ajaxURL, smartSmtpNonce } = dashboardData;
```

- [ ] **Step 3: Add `emailStatus` and `isInstallingSmtp` state**

After the existing `const [testEmail, setTestEmail] = useState<string>(adminEmail || '');` line, add:

```typescript
const [emailStatus, setEmailStatus] = useState<'idle' | 'sent' | 'failed'>('idle');
const [isInstallingSmtp, setIsInstallingSmtp] = useState(false);
const [smtpInstallError, setSmtpInstallError] = useState<string | null>(null);
```

- [ ] **Step 4: Initialize emailStatus from server state on mount**

Add this `useEffect` after the existing `useEffect` that sets `testEmail` from `adminEmail` (around line 695):

```typescript
useEffect(() => {
  if (
    siteData?.data?.last_form_email_status === 'failed' &&
    !siteData?.data?.test_email_sent &&
    emailStatus === 'idle'
  ) {
    setEmailStatus('failed');
  }
}, [siteData?.data?.last_form_email_status]);
```

- [ ] **Step 5: Update `sendTestEmailMutation` `onSuccess` handler**

Find the `sendTestEmailMutation` `onSuccess` (around line 168). Replace its entire body with:

```typescript
onSuccess: (data) => {
  queryClient.setQueryData(['siteAssistant'], data);
  setEmailStatus(data.data.email_sent ? 'sent' : 'failed');
},
onError: (error: any) => {
  console.error('Error sending test email:', error);
  setEmailStatus('failed');
},
```

Also remove the `setTestEmail('')` call and the toast calls that were previously there — the inline banner replaces the toast for success/failure feedback.

- [ ] **Step 6: Build and check for TypeScript errors**

```bash
cd C:/laragon/www/evf/wp-content/plugins/everest-forms
npm run dev
```

Expected: webpack compiles without TypeScript errors. Watch the terminal output — fix any type errors before proceeding.

- [ ] **Step 7: Commit**

```bash
git add src/dashboard/screens/SiteAssistant/SiteAssistant.tsx
git commit -m "feat: add emailStatus state and wire sendTestEmailMutation to inline status"
```

---

### Task 8: Add Chakra UI Alert imports and inline status banner

**Files:**
- Modify: `src/dashboard/screens/SiteAssistant/SiteAssistant.tsx`

- [ ] **Step 1: Add Alert components to Chakra UI imports**

Find the existing `@chakra-ui/react` import block (top of file, around line 5). Add `Alert, AlertIcon` to the destructured imports:

```typescript
import {
  Alert,
  AlertIcon,
  Box,
  Button,
  Collapse,
  Container,
  Divider,
  Flex,
  FormControl,
  Grid,
  Heading,
  HStack,
  Icon,
  IconButton,
  Image,
  Input,
  Link,
  Stack,
  Text,
  useToast,
} from '@chakra-ui/react';
```

- [ ] **Step 2: Add the status banner inside `renderSendTestEmailContent()`**

Find `renderSendTestEmailContent()` (around line 551). Inside the `<Collapse in={open?.sendTestEmail}>` block, after `<Divider color={'gray.200'} />` and before the description `<Text>`, insert:

```tsx
{emailStatus === 'failed' && (
  <Alert status="error" borderRadius="md" fontSize="sm">
    <AlertIcon />
    <Text fontSize="sm">
      <strong>{__('Test Email Failed', 'everest-forms')}</strong>
      {' – '}
      {__(
        "Your server's default mail function appears unreliable. A dedicated SMTP plugin will fix this.",
        'everest-forms',
      )}
    </Text>
  </Alert>
)}
{emailStatus === 'sent' && (
  <Alert status="success" borderRadius="md" fontSize="sm">
    <AlertIcon />
    <Text fontSize="sm">
      <strong>{__('Test Email Sent Successfully', 'everest-forms')}</strong>
      {' – '}
      {__(
        'Your email delivery is working. Form notifications should reach your inbox reliably.',
        'everest-forms',
      )}
    </Text>
  </Alert>
)}
```

- [ ] **Step 3: Build and visually verify banner renders**

```bash
npm run dev
```

Navigate to the Site Assistant wizard. Click "Send Test Email" with a valid email. Verify:
- On success: green banner appears below the chevron
- On failure (you can simulate by adding `add_filter('pre_wp_mail', '__return_false')` temporarily in PHP): red banner appears

- [ ] **Step 4: Commit**

```bash
git add src/dashboard/screens/SiteAssistant/SiteAssistant.tsx
git commit -m "feat: add inline red/green email status banner in Send Test Email wizard step"
```

---

### Task 9: Add Smart SMTP install card with inline AJAX install

**Files:**
- Modify: `src/dashboard/screens/SiteAssistant/SiteAssistant.tsx`

- [ ] **Step 1: Add `BiEnvelope` to react-icons import**

Find the `react-icons/bi` import (around line 32):
```typescript
import { BiChevronDown, BiChevronUp } from 'react-icons/bi';
```

Replace with:
```typescript
import { BiChevronDown, BiChevronUp, BiEnvelope } from 'react-icons/bi';
```

- [ ] **Step 2: Add `handleInstallSmtpPlugin` handler**

After `handleSendTestEmail` (around line 249), add:

```typescript
const handleInstallSmtpPlugin = async () => {
  setIsInstallingSmtp(true);
  setSmtpInstallError(null);
  try {
    const formData = new FormData();
    formData.append('action', 'install_and_activate_smart_smtp');
    formData.append('security', smartSmtpNonce || '');
    const response = await fetch(ajaxURL || '', {
      method: 'POST',
      body: formData,
    });
    const result = await response.json();
    if (result.success) {
      queryClient.setQueryData(['siteAssistant'], (old: any) => ({
        ...old,
        data: {
          ...old?.data,
          is_smart_smtp_installed: true,
          is_smart_smtp_active: true,
        },
      }));
    } else {
      setSmtpInstallError(
        result.data?.message ||
          __('Installation failed. Please try manually.', 'everest-forms'),
      );
    }
  } catch {
    setSmtpInstallError(__('Installation failed. Please try manually.', 'everest-forms'));
  } finally {
    setIsInstallingSmtp(false);
  }
};
```

- [ ] **Step 3: Add Smart SMTP card inside `renderSendTestEmailContent()`**

In `renderSendTestEmailContent()`, after the status banner block (after the closing `}`  of the `emailStatus === 'sent'` Alert), and before the description `<Text>` block, insert:

```tsx
{(emailStatus === 'failed' || emailStatus === 'sent') &&
  !siteData?.data?.is_smart_smtp_active && (
    <Box
      p={4}
      border="1px"
      borderColor="gray.200"
      borderRadius="md"
      bg="gray.50"
    >
      <Flex justify="space-between" align="center" gap={4}>
        <HStack align="flex-start" spacing={3} flex={1}>
          <Box
            p={2}
            bg="primary.15"
            borderRadius="md"
            flexShrink={0}
            mt="1px"
          >
            <Icon as={BiEnvelope} fontSize="xl" color="primary.500" />
          </Box>
          <Box>
            <HStack spacing={1} mb={1}>
              <Text fontSize="sm" fontWeight="600" color="grey.500">
                {emailStatus === 'failed'
                  ? __('Fix this with SmartSMTP', 'everest-forms')
                  : __('Want more reliable delivery?', 'everest-forms')}
              </Text>
              <Link
                href="https://wordpress.org/plugins/smart-smtp/"
                isExternal
                color="primary.500"
                fontSize="sm"
              >
                ↗
              </Link>
            </HStack>
            <Text fontSize="xs" color="grey.350" lineHeight="1.5">
              {emailStatus === 'failed'
                ? __(
                    'SmartSMTP sends emails through a proper mail service instead of your hosting server.',
                    'everest-forms',
                  )
                : __(
                    "SmartSMTP adds proper email authentication so your notifications don't end up in spam.",
                    'everest-forms',
                  )}
            </Text>
            {smtpInstallError && (
              <Text fontSize="xs" color="red.500" mt={1}>
                {smtpInstallError}
              </Text>
            )}
          </Box>
        </HStack>
        <Button
          colorScheme="blue"
          size="sm"
          onClick={handleInstallSmtpPlugin}
          isLoading={isInstallingSmtp}
          loadingText={__('Installing...', 'everest-forms')}
          flexShrink={0}
        >
          {siteData?.data?.is_smart_smtp_installed
            ? __('Activate Plugin', 'everest-forms')
            : __('Install Plugin', 'everest-forms')}
        </Button>
      </Flex>
    </Box>
  )}
```

- [ ] **Step 4: Build and verify the full flow in the browser**

```bash
npm run dev
```

Navigate to Site Assistant → Send Test Email. Test both paths:

**Failure path:** Temporarily add `add_filter('pre_wp_mail', '__return_false');` in `includes/class-evf-emails.php` just before the `return $sent;` line. Send a test email. Verify:
- Red banner appears: "Test Email Failed – Your server's default mail function appears unreliable..."
- Smart SMTP card appears with "Fix this with SmartSMTP" title and "Install Plugin" button
- Clicking "Install Plugin" shows "Installing..." state
- Remove the temporary filter after testing.

**Success path:** Send a real test email. Verify:
- Green banner: "Test Email Sent Successfully – Your email delivery is working..."
- Smart SMTP card shows "Want more reliable delivery?" with "Install Plugin" button
- After install completes: card disappears, banner remains

**Already active path:** Activate Smart SMTP manually. Reload wizard. Send test email. Verify:
- Only the green/red banner shows — no SMTP card.

- [ ] **Step 5: Commit**

```bash
git add src/dashboard/screens/SiteAssistant/SiteAssistant.tsx
git commit -m "feat: add Smart SMTP install card with inline AJAX install to Send Test Email wizard step"
```

---

### Task 10: Production build and final verification

**Files:** None — build artifact only.

- [ ] **Step 1: Run production build**

```bash
npm run build:core
```

Expected: no errors; `dist/` assets updated.

- [ ] **Step 2: Run full PHPUnit suite**

```bash
vendor/bin/phpunit tests/phpunit/ --verbose
```

Expected: all tests pass, no regressions.

- [ ] **Step 3: End-to-end manual test — form submission email failure path**

1. Create a simple contact form in Everest Forms.
2. Temporarily break outgoing mail: add `add_filter('wp_mail', function($args) { return false; }, 99);` to `wp-config.php` or a mu-plugin.
3. Submit the form from the frontend.
4. Navigate to Everest Forms → Dashboard (Site Assistant).
5. Open the "Send Test Email" step.

Expected: red banner ("Test Email Failed") and Smart SMTP install card appear without sending a test email — state loaded from `everest_forms_last_form_email_status`.

6. Remove the `wp_mail` filter. Submit the form again.
7. Reload the Site Assistant. Open the "Send Test Email" step.

Expected: green banner ("Test Email Sent Successfully") and the SMTP install card.

- [ ] **Step 4: Final commit**

```bash
git add dist/
git commit -m "build: compile production assets for email delivery wizard setup"
```
