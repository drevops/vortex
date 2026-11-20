# Secure code examples

Each example uses the `sw_base` custom module. Replace the names with the module the change belongs to. Match the surrounding module's style for hooks, dependency injection and file layout.

## Route requirements

```yaml
# sw_base.routing.yml
sw_base.report:
  path: '/admin/reports/sw-base'
  defaults:
    _controller: '\Drupal\sw_base\Controller\ReportController::view'
    _title: 'Site report'
  requirements:
    _permission: 'view sw_base reports'

sw_base.article_review:
  path: '/node/{node}/review'
  defaults:
    _form: '\Drupal\sw_base\Form\ReviewForm'
  requirements:
    _entity_access: 'node.update'
    node: \d+
  options:
    parameters:
      node:
        type: entity:node

sw_base.subscription_cancel:
  path: '/subscription/{subscription_id}/cancel'
  defaults:
    _controller: '\Drupal\sw_base\Controller\SubscriptionController::cancel'
  requirements:
    _custom_access: '\Drupal\sw_base\Access\SubscriptionAccess::access'
    _csrf_token: 'TRUE'
    subscription_id: \d+
```

```yaml
# sw_base.permissions.yml
view sw_base reports:
  title: 'View site reports'
administer sw_base:
  title: 'Administer site integrations'
  restrict access: true
```

## Custom access with cacheability

```php
namespace Drupal\sw_base\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;

class SubscriptionAccess {

  public function access(int $subscription_id, AccountInterface $account): AccessResultInterface {
    if (!$account->hasPermission('cancel own subscription')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }

    $owner_id = $this->loadOwnerId($subscription_id);

    // The result depends on the account, so it varies per user.
    return AccessResult::allowedIf($owner_id === (int) $account->id())->cachePerPermissions()->cachePerUser();
  }

}
```

## Entity queries

```php
// Content listed to the current user: node access and published state apply.
$nids = $this->entityTypeManager->getStorage('node')->getQuery()
  ->accessCheck(TRUE)
  ->condition('type', 'article')
  ->condition('status', 1)
  ->range(0, 10)
  ->execute();

// System work in a queue worker: no user is involved.
$nids = $this->entityTypeManager->getStorage('node')->getQuery()
  ->accessCheck(FALSE)
  ->condition('field_sync_pending', 1)
  ->execute();

// An entity loaded from a request parameter.
$node = $this->entityTypeManager->getStorage('node')->load($nid);
$access = $node?->access('view', $this->currentUser, TRUE);
if ($node === NULL || !$access->isAllowed()) {
  throw new AccessDeniedHttpException();
}
```

## Database queries

```php
// Named placeholders.
$result = $this->database->query('SELECT nid, title FROM {node_field_data} WHERE type = :type AND langcode = :langcode', [
  ':type' => $type,
  ':langcode' => $langcode,
]);

// A list of values.
$result = $this->database->query('SELECT nid FROM {node_field_data} WHERE nid IN (:nids[])', [':nids[]' => $nids]);

// LIKE with user input.
$query = $this->database->select('node_field_data', 'n')
  ->fields('n', ['nid', 'title'])
  ->condition('n.title', '%' . $this->database->escapeLike($search) . '%', 'LIKE');

// Sort field and direction from the query string, mapped onto an allowlist.
$sort_fields = ['title' => 'n.title', 'date' => 'n.created'];
$field = $sort_fields[$request->query->get('sort')] ?? 'n.created';
$direction = strtoupper((string) $request->query->get('order')) === 'ASC' ? 'ASC' : 'DESC';
$query->orderBy($field, $direction);
```

## Output

```php
// Text from a user or an external service.
$build['name'] = ['#plain_text' => $profile_name];

// HTML that belongs to a text format.
$build['body'] = [
  '#type' => 'processed_text',
  '#text' => $item->value,
  '#format' => $item->format,
];

// Translatable strings with values.
$build['status'] = [
  '#markup' => $this->t('Imported %count items from @source. <a href=":url">View the log</a>.', [
    '%count' => $count,
    '@source' => $source_label,
    ':url' => $log_url->toString(),
  ]),
];

// Markup assembled in code from escaped parts.
$build['badge'] = ['#markup' => Markup::create('<span class="badge">' . Html::escape($label) . '</span>')];
```

```twig
{# Escaped automatically. #}
<h2>{{ title }}</h2>

{# A render array from PHP; never a user value with |raw. #}
{{ content.body }}

{# Attributes escape their values. #}
<div{{ attributes.addClass('summary') }}>{{ summary }}</div>
```

```js
// Drupal.behaviors with once(), writing text, not HTML.
Drupal.behaviors.ysBaseCounter = {
  attach(context, settings) {
    once('sw-base-counter', '.js-sw-base-counter', context).forEach((element) => {
      element.textContent = settings.ysBase.counterLabel;
    });
  },
};
```

## Cross-site request forgery

```php
// A link to a route with '_csrf_token: TRUE' gets its token added.
$url = Url::fromRoute('sw_base.subscription_cancel', ['subscription_id' => $id]);

// A token for a JavaScript endpoint, validated in the controller.
$build['#attached']['drupalSettings']['ysBase']['token'] = $this->csrfToken->get('sw_base/vote');

if (!$this->csrfToken->validate((string) $request->headers->get('X-Ys-Base-Token'), 'sw_base/vote')) {
  throw new AccessDeniedHttpException();
}
```

## File uploads

```php
$form['document'] = [
  '#type' => 'managed_file',
  '#title' => $this->t('Document'),
  '#upload_location' => 'private://sw_base/documents',
  '#upload_validators' => [
    'FileExtension' => ['extensions' => 'pdf docx'],
    'FileSizeLimit' => ['fileLimit' => 10 * 1024 * 1024],
  ],
  '#required' => TRUE,
];
```

```php
/**
 * Implements hook_file_download().
 */
function sw_base_file_download(string $uri): array|int|null {
  if (!str_starts_with($uri, 'private://sw_base/reports/')) {
    return NULL;
  }

  if (!\Drupal::currentUser()->hasPermission('view sw_base reports')) {
    return -1;
  }

  return ['Content-Type' => 'application/pdf'];
}
```

## Secrets

```php
// web/sites/default/includes/modules/settings.sw_base.php
declare(strict_types=1);

$sw_base_api_key = getenv('DRUPAL_YS_BASE_API_KEY');
if (!empty($sw_base_api_key)) {
  $settings['sw_base_api_key'] = $sw_base_api_key;
}
```

```php
// A service that receives '@settings' and '@http_client'.
$api_key = $this->settings->get('sw_base_api_key');
if (empty($api_key)) {
  $this->logger->warning('The partner API key is not configured.');

  return [];
}

$response = $this->httpClient->request('GET', 'https://api.partner.example/v1/items', [
  'headers' => ['Authorization' => 'Bearer ' . $api_key],
  'timeout' => 5,
]);
```

```yaml
# docker-compose.override.yml, for local web requests only.
services:
  php:
    environment:
      DRUPAL_YS_BASE_API_KEY: ${DRUPAL_YS_BASE_API_KEY:-}
```

## Outbound requests to a URL from input

```php
$allowed_hosts = ['api.partner.example', 'cdn.partner.example'];
$parts = parse_url($feed_url);
if (($parts['scheme'] ?? '') !== 'https' || !in_array($parts['host'] ?? '', $allowed_hosts, TRUE)) {
  throw new \InvalidArgumentException('Feed URL is not on the allowlist.');
}

$response = $this->httpClient->request('GET', $feed_url, ['timeout' => 5, 'allow_redirects' => FALSE]);
```

## Logging

```php
$this->logger = $logger_factory->get('sw_base');

// Placeholders, and an ID instead of personal data.
$this->logger->error('Order @order_id failed to sync: @reason', [
  '@order_id' => $order->id(),
  '@reason' => $reason,
]);

// Exceptions, after checking the message can't hold a secret.
Error::logException($this->logger, $exception);
$this->messenger->addError($this->t('The order could not be synced. Try again later.'));
```
