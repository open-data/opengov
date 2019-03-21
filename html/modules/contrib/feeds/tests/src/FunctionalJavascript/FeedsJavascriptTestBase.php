<?php

namespace Drupal\Tests\feeds\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Entity\FeedType;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Base class for Feeds javascript tests.
 */
abstract class FeedsJavascriptTestBase extends JavascriptTestBase {

  use CronRunTrait;
  use FeedCreationTrait;
  use FeedsCommonTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'feeds',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   *
   * Do not use PhantomJS.
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a content type.
    $this->setUpNodeType();

    // Create an user with Feeds admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Starts a batch import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import.
   */
  protected function batchImport(FeedInterface $feed) {
    $this->drupalGet('feed/' . $feed->id() . '/import');
    $this->submitForm([], 'Import');
  }

  /**
   * Adds mappings to the given feed type via the UI.
   *
   * @param string $feed_type_id
   *   The feed type ID.
   * @param array $mappings
   *   An array of mapping arrays. Each mapping array can consist of the
   *   following keys:
   *   - target: (required) the target to map to.
   *   - map: (required) an array of mapping sources. The keys are expected to
   *     represent the name of the subtarget (in most cases 'value'). The value
   *     is the source key to map to. If the value is a string, it is expected
   *     to represent an existing or a predefined source. If it is an array, it
   *     is expected to represent a custom source. In this case, specify the
   *     details of the custom source:
   *     - value: (required) the value to extract from the feed.
   *     - label: (optional) the custom source's label.
   *     - machine_name: (required) the custom source's machine name.
   *   - unique: (optional) an array of mapping targets to set as unique or not.
   *     The keys are the name of the subtargets, the value is a boolean: 'true'
   *     for setting as unique, 'false' for not.
   * @param array $edit
   *   (optional) Additional field values to submit.
   * @param bool $assert_mappings
   *   (optional) Whether or not to assert the mappings. Defaults to true.
   *
   * @todo add support for setting target configuration.
   */
  protected function addMappings($feed_type_id, array $mappings, array $edit = [], $assert_mappings = TRUE) {
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type_id . '/mapping');

    foreach ($mappings as $i => $mapping) {
      // Add target.
      $this->assertSession()->fieldExists('add_target');
      $this->getSession()->getPage()->selectFieldOption('add_target', $mapping['target']);
      $this->assertSession()->assertWaitOnAjaxRequest();

      // Select sources.
      foreach ($mapping['map'] as $key => $source) {
        if (is_array($source)) {
          // Custom source.
          $this->assertSession()->fieldExists("mappings[$i][map][$key][select]");
          $this->getSession()->getPage()->selectFieldOption("mappings[$i][map][$key][select]", '__new');

          $edit["mappings[$i][map][$key][select]"] = '__new';
          foreach ($source as $source_key => $source_value) {
            $edit["mappings[$i][map][$key][__new][$source_key]"] = $source_value;
          }
        }
        else {
          // Existing or predefined source.
          $edit["mappings[$i][map][$key][select]"] = $source;
        }
      }

      // Set uniques, if specified.
      if (isset($mapping['unique'])) {
        foreach ($mapping['unique'] as $key => $enabled) {
          $edit["mappings[$i][unique][$key]"] = $enabled;
        }
      }
    }

    // Set the form values, including machine name.
    $assert_session = $this->assertSession();
    $submit_button = $assert_session->buttonExists('Save');
    $form = $assert_session->elementExists('xpath', './ancestor::form', $submit_button);
    foreach ($edit as $name => $value) {
      if (strpos($name, '[__new][machine_name]') !== FALSE) {
        // Make machine name appear.
        $i = preg_replace('/^mappings\[([0-9]+)\].+$/', '${1}', $name);
        $key = preg_replace('/^.+\[map\]\[([^\]]+)\].+$/', '${1}', $name);
        $key = Html::cleanCssIdentifier($key);
        $base_xpath = 'descendant-or-self::div[@data-drupal-selector=\'edit-mappings-' . $i . '-map-' . $key . '-new\']';

        // First, wait for machine name button to become visible.
        $xpath = $base_xpath . '/descendant-or-self::*/button';
        $this->assertSession()->waitForElementVisible('xpath', $xpath);
        // Click button.
        $this->getSession()->getDriver()->click($xpath);

        // Wait for machine name text field to become visible.
        $xpath = $base_xpath . '/' . $this->cssSelectToXpath('div.form-type-machine-name input.machine-name-target');
        $this->assertSession()->waitForElementVisible('xpath', $xpath);
      }
      $field = $assert_session->fieldExists($name, $form);
      $field->setValue($value);
    }

    $this->submitForm($edit, 'Save');

    // Assert that the mappings and custom sources were successfully added.
    if ($assert_mappings) {
      $feed_type = FeedType::load($feed_type_id);
      $feed_type = $this->reloadEntity($feed_type);
      $saved_mappings = $feed_type->getMappings();

      foreach ($mappings as $i => $mapping) {
        // Assert target.
        $this->assertEquals($mapping['target'], $saved_mappings[$i]['target']);

        // Assert map.
        foreach ($mapping['map'] as $key => $source) {
          if (is_array($source)) {
            $this->assertEquals($source['machine_name'], $saved_mappings[$i]['map'][$key]);
            // Assert custom source.
            $custom_source = $feed_type->getCustomSource($source['machine_name']);
            foreach ($source as $source_key => $source_value) {
              $this->assertEquals($source_value, $custom_source[$source_key]);
            }
          }
          else {
            $this->assertEquals($source, $saved_mappings[$i]['map'][$key]);
          }
        }

        // Assert unique.
        if (isset($mapping['unique'])) {
          $this->assertEquals($mapping['unique'], $saved_mappings[$i]['unique']);
        }
      }
    }
  }

  /**
   * Fills and submits a form where the submit button is hidden in a dropbutton.
   *
   * @param array $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   *
   *   A checkbox can be set to TRUE to be checked and should be set to FALSE to
   *   be unchecked.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   'Save'. The processing of the request depends on this value. For example,
   *   a form may have one button with the value 'Save' and another button with
   *   the value 'Delete', and execute different code depending on which one is
   *   clicked.
   * @param string $form_html_id
   *   (optional) HTML ID of the form to be submitted. On some pages
   *   there are many identical forms, so just using the value of the submit
   *   button is not enough. For example: 'trigger-node-presave-assign-form'.
   *   Note that this is not the Drupal $form_id, but rather the HTML ID of the
   *   form, which is typically the same thing but with hyphens replacing the
   *   underscores.
   */
  protected function submitFormWithDropButton(array $edit, $submit, $form_html_id = NULL) {
    $assert_session = $this->assertSession();

    // Get the form.
    if (isset($form_html_id)) {
      $form = $assert_session->elementExists('xpath', "//form[@id='$form_html_id']");
      $submit_button = $assert_session->buttonExists($submit, $form);
      $action = $form->getAttribute('action');
    }
    else {
      $submit_button = $assert_session->buttonExists($submit);
      $form = $assert_session->elementExists('xpath', './ancestor::form', $submit_button);
      $action = $form->getAttribute('action');
    }

    // Edit the form values.
    foreach ($edit as $name => $value) {
      $field = $assert_session->fieldExists($name, $form);
      $field->setValue($value);
    }

    // Submit form.
    $this->prepareRequest();

    // Click dropbutton and wait until the secondary action becomes visible.
    $this->click('#edit-actions .dropbutton-toggle button');
    $assert_session->waitForElementVisible('css', '#edit-actions .dropbutton-widget .secondary-action');

    $submit_button->press();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Check if there are any meta refresh redirects (like Batch API pages).
    if ($this->checkForMetaRefresh()) {
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }
  }

}
