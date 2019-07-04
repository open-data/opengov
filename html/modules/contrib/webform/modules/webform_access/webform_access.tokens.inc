<?php

/**
 * @file
 * Builds placeholder replacement tokens for webform access type.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\webform_access\Entity\WebformAccessType;

/**
 * Implements hook_token_info().
 */
function webform_access_token_info() {
  $types = [];
  $types['webform_access'] = [
    'name' => t('Webform access'),
    'description' => t("Tokens related to webform access group types. <em>This token is only available to a Webform email handler's 'To', 'CC', and 'BCC' email recipents.</em>"),
    'needs-data' => 'webform_access',
  ];

  $tokens = [];
  $webform_access = [];
  $webform_access_types = WebformAccessType::loadMultiple();
  $webform_access['type'] = [
    'name' => t('All users'),
    'description' => t('The email addresses of all users assigned to the current webform.'),
  ];
  foreach ($webform_access_types as $webform_access_type_name => $webform_access_type) {
    $webform_access['type:' . $webform_access_type_name] = [
      'name' => $webform_access_type->label(),
      'description' => t('The email addresses of all webform users assigned to the %title access type for the current webform.', ['%title' => $webform_access_type->label()]),
    ];
  }
  $tokens['webform_access'] = $webform_access;

  /****************************************************************************/

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function webform_access_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'webform_access' && !empty($data['webform_access'])) {
    /** @var \Drupal\webform_access\WebformAccessGroupStorageInterface $webform_access_group_storage */
    $webform_access_group_storage = \Drupal::entityTypeManager()->getStorage('webform_access_group');

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submision */
    $webform_submision = $data['webform_access'];
    $webform = $webform_submision->getWebform();
    $source_entity = $webform_submision->getSourceEntity();
    foreach ($tokens as $name => $original) {
      $webform_access_type_id = ($name === 'type') ? NULL : str_replace('type:', '', $name);

      /** @var \Drupal\webform_access\WebformAccessGroupStorageInterface $webform_access_group */
      $webform_access_groups = $webform_access_group_storage->loadByEntities($webform, $source_entity, NULL, $webform_access_type_id);

      if ($webform_access_groups) {
        $query = \Drupal::database()->select('webform_access_group_user', 'gu');
        $query->condition('gu.group_id', array_keys($webform_access_groups), 'IN');
        $query->join('users_field_data', 'u', 'u.uid = gu.uid');
        $query->fields('u', ['mail']);
        $query->condition('u.status', 1);
        $query->condition('u.mail', '', '<>');
        $query->orderBy('mail');
        $query->distinct();

        $replacements[$original] = implode(',', $query->execute()->fetchCol());
      }
    }
  }

  return $replacements;
}
