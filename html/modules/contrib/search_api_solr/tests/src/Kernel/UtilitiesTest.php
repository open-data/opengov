<?php

namespace Drupal\Tests\search_api_solr\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_solr\Utility\Utility;

/**
 * Provides tests for various utility functions.
 *
 * @group search_api_solr
 */
class UtilitiesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search_api',
    'search_api_solr',
    'user',
  ];

  /**
   * Tests encoding and decoding of Solr field names.
   */
  public function testFieldNameEncoder() {
    $allowed_characters_pattern = '/[a-zA-Z\d_]/';
    $forbidden_field_name = 'forbidden$field_nameÜöÄ*:/;#last_XMas';
    $expected_encoded_field_name = 'forbidden_X24_field_name_Xc39c__Xc3b6__Xc384__X2a__X3a__X2f__X3b__X23_last_X5f58_Mas';
    $encoded_field_name = Utility::encodeSolrName($forbidden_field_name);

    $this->assertEquals($encoded_field_name, $expected_encoded_field_name);

    preg_match_all($allowed_characters_pattern, $encoded_field_name, $matches);
    $this->assertEquals(count($matches[0]), strlen($encoded_field_name), 'Solr field name consists of allowed characters.');

    $decoded_field_name = Utility::decodeSolrName($encoded_field_name);

    $this->assertEquals($decoded_field_name, $forbidden_field_name);

    $this->assertEquals('ss_field_foo', Utility::encodeSolrName('ss_field_foo'));
  }

  /**
   * Tests language-specific Solr field names.
   */
  public function testLanguageSpecificFieldTypeNames() {
    $this->assertEquals('text_de', Utility::encodeSolrName('text_de'));

    // Drupal-like locale for Austria.
    $encoded = Utility::encodeSolrName('text_de-at');
    $this->assertEquals('text_de_X2d_at', $encoded);
    $this->assertEquals('text_de-at', Utility::decodeSolrName($encoded));

    // Traditional Chinese as used in Hong Kong.
    $encoded = Utility::encodeSolrName('text_zh-Hant-HK');
    $this->assertEquals('text_zh_X2d_Hant_X2d_HK', $encoded);
    $this->assertEquals('text_zh-Hant-HK', Utility::decodeSolrName($encoded));

    // The variant of German orthography dating from the 1901 reforms, as seen
    // in Switzerland.
    $encoded = Utility::encodeSolrName('text_de-CH-1901');
    $this->assertEquals('text_de_X2d_CH_X2d_1901', $encoded);
    $this->assertEquals('text_de-CH-1901', Utility::decodeSolrName($encoded));
  }

  /**
   * Tests merge default index third-party settings.
   */
  public function testMergeDefaultIndexThirdPartySettings() {
    $third_party_settings = [
      'finalize' => TRUE,
      'commit_before_finalize' => FALSE,
      'highlighter' => [
        'maxAnalyzedChars' => 51200,
        'fragmenter' => 'gap',
        'usePhraseHighlighter' => TRUE,
        'highlightMultiTerm' => TRUE,
        'preserveMulti' => FALSE,
        'regex' => [
          'slop' => 0.9,
          'maxAnalyzedChars' => 2,
        ],
      ],
      'advanced' => [
        'index_prefix' => 'dummy',
      ],
      'multilingual' => [
        'limit_to_content_language' => TRUE,
        'include_language_independent' => TRUE,
        'specific_languages' => [
          'en' => '0',
          'de' => 'de',
        ],
      ],
    ];

    $this->assertEquals(
      [
        'finalize' => TRUE,
        'commit_before_finalize' => FALSE,
        'commit_after_finalize' => FALSE,
        'highlighter' => [
          'maxAnalyzedChars' => 51200,
          'fragmenter' => 'gap',
          'usePhraseHighlighter' => TRUE,
          'highlightMultiTerm' => TRUE,
          'preserveMulti' => FALSE,
          'regex' => [
            'slop' => 0.9,
            'pattern' => 'blank',
            'maxAnalyzedChars' => 2,
          ],
          'highlight' => [
            'mergeContiguous' => FALSE,
            'requireFieldMatch' => FALSE,
            'snippets' => 3,
            'fragsize' => 0,
          ],
        ],
        'mlt' => [
          'mintf' => 1,
          'mindf' => 1,
          'maxdf' => 0,
          'maxdfpct' => 0,
          'minwl' => 0,
          'maxwl' => 0,
          'maxqt' => 100,
          'maxntp' => 2000,
          'boost' => FALSE,
          'interestingTerms' => 'none',
        ],
        'advanced' => [
          'index_prefix' => 'dummy',
          'collection' => '',
          'timezone' => '',
        ],
        'multilingual' => [
          'limit_to_content_language' => TRUE,
          'include_language_independent' => TRUE,
          'specific_languages' => [
            'en' => '0',
            'de' => 'de',
          ],
          'use_language_undefined_as_fallback_language' => FALSE,
          'use_universal_collation' => FALSE,
        ],
        'term_modifiers' => [
          'slop' => 3,
          'fuzzy' => 1,
          'fuzzy_analyzer' => TRUE,
        ],
        'debug_finalize' => FALSE,
      ],
      search_api_solr_merge_default_index_third_party_settings($third_party_settings)
    );
  }

  /**
   * Tests extracting of highlighted keys.
   */
  public function testHighlightedKeys() {
    $snippet = '';
    $highlighted_keys = Utility::getHighlightedKeys($snippet);

    $this->assertEquals([], $highlighted_keys);
  }

  /**
   * Tests preserving third-party settings missing from the index form.
   */
  public function testSearchApiIndexFormEntityBuilderPreservesThirdPartySettings() {
    $original_index = Index::create([
      'id' => 'test_index',
      'name' => 'Test index',
    ]);
    $original_index->setThirdPartySetting('search_api_solr', 'finalize', FALSE);
    $original_index->setThirdPartySetting('other_module', 'existing', 'preserved');
    $original_index->setThirdPartySetting('submitted_module', 'existing', 'old');

    $submitted_settings = [
      'search_api_solr' => [
        'finalize' => TRUE,
      ],
      'submitted_module' => [
        'changed' => 'new',
      ],
    ];

    $index = Index::create([
      'id' => 'test_index',
      'name' => 'Test index',
    ]);
    foreach ($submitted_settings as $provider => $settings) {
      foreach ($settings as $key => $value) {
        $index->setThirdPartySetting($provider, $key, $value);
      }
    }

    $form_state = (new FormState())
      ->setFormObject(new class($original_index) implements FormInterface {

        /**
         * The original index entity.
         *
         * @var \Drupal\search_api\Entity\Index
         */
        protected $index;

        /**
         * Constructs a new test form object.
         *
         * @param \Drupal\search_api\Entity\Index $index
         *   The original index entity.
         */
        public function __construct(Index $index) {
          $this->index = $index;
        }

        /**
         * Gets the original index entity.
         *
         * @return \Drupal\search_api\Entity\Index
         *   The original index entity.
         */
        public function getEntity() {
          return $this->index;
        }

        /**
         * {@inheritdoc}
         */
        public function getFormId() {
          return 'search_api_index_edit_form';
        }

        /**
         * {@inheritdoc}
         */
        public function buildForm(array $form, FormStateInterface $form_state) {
          return $form;
        }

        /**
         * {@inheritdoc}
         */
        public function validateForm(array &$form, FormStateInterface $form_state) {}

        /**
         * {@inheritdoc}
         */
        public function submitForm(array &$form, FormStateInterface $form_state) {}

      })
      ->setValue('third_party_settings', $submitted_settings);
    $form = [];

    search_api_solr_search_api_index_form_entity_builder('search_api_index', $index, $form, $form_state);

    $this->assertTrue($index->getThirdPartySetting('search_api_solr', 'finalize'));
    $this->assertSame('preserved', $index->getThirdPartySetting('other_module', 'existing'));
    $this->assertNull($index->getThirdPartySetting('submitted_module', 'existing'));
    $this->assertSame('new', $index->getThirdPartySetting('submitted_module', 'changed'));
  }

}
