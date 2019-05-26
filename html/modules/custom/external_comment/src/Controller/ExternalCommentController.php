<?php

namespace Drupal\external_comment\Controller;

use Drupal\comment\Controller\CommentController;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;

/**
 * Class ExternalCommentController.
 * A wrapper class of the Drupal Core CommentController to handle comments from entities outside Drupal
 */
class ExternalCommentController extends CommentController {

  /**
   * Render comment form for entities external to Drupal
   * @param Request $request
   * @param $uuid
   * @return Response
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function renderExternalComment(Request $request, $ext_type, $uuid) {
    $renderHTML = '';

    // only display the form if validated
    if ($this->validate($request, $ext_type, $uuid)) {
      // check if comments exist for this uuid
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'external')
        ->condition('status', 1)
        ->condition('field_type', $ext_type)
        ->condition('field_uuid', $uuid);
      $results = $query->execute();

      // if comments do not exist for this uuid then load the default node
      if (!$results) {
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'external')
          ->condition('status', 1)
          ->condition('field_uuid', 'default');
        $results = $query->execute();
      }

      if ($results) {
        $node_id = $results[array_keys($results)[0]];
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

        // if node exist then load the node with comments
        if ($node) {
          $css = '<link rel="stylesheet" type="text/css" href="/modules/custom/external_comment/css/style.css" />';
          $commentsHTML = comment_node_update_index($node);
          $renderHTML .= ($commentsHTML) ? $css . '<h2>' . t('Comments') . '</h2>' . $commentsHTML : '';

          // Load comment form
          $commentForm = $this->getReplyForm($request, $node, 'comment');
          $commentForm['comment_form']['#action'] = ($node->get('title')->value == 'default')
            ? str_replace('/comment/', '/external_comment/', $commentForm['comment_form']['#action'])
            : $commentForm['comment_form']['#action'];
          $commentFormHTML = \Drupal::service('renderer')->render($commentForm);
          $ProcessedCommentFormHTML = explode('</h2>', $commentFormHTML);

          // concatenate HTML to generate final HTML
          $renderHTML .= '<h2>' . t('Add new comment') . '</h2>' . $ProcessedCommentFormHTML[1] . '<br/>';
        }
      }
   }

    // return response with HTML
    return new Response($renderHTML);
  }

  /**
   * Wrapper function of the getReplyForm to create new nodes when commenting on default comment form
   * @param Request $request
   * @param EntityInterface $entity
   * @param string $field_name
   * @param null $pid
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getReplyForm(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {

    // if commented on default node then create a new node and attach comment
    if ($request->getMethod() == 'POST' && $entity->getEntityTypeId() == 'node' && $entity->bundle() == 'external'
      && $entity->get('title')->value == 'default' ) {

      // get url, uuid, title and type of request object
      $url = $request->headers->get('referer');
      $url_explode = explode("/",$url);
      $uuid = end($url_explode);
      $ext_type = prev($url_explode);

      if ($this->validate($request,$ext_type, $uuid)) {
        // create node with the information gathered above
        $lang = strpos($url, '/en/') ? 'en' : 'fr';
        $url_en = $lang == 'en' ? $url : str_replace('/fr/', '/en/', $url);
        $url_fr = $lang == 'en' ? str_replace('/en/', '/fr/', $url) : $url;
        $types_en = array(
          "dataset" => "Dataset",
          "visualization" => "Data Visualization",
          "pd" => "Proactive Disclosure",
          "inventory" => "Open Data Inventory",
        );
        $ext_type_en = $types_en[$ext_type];
        $types_fr = array(
          "dataset" => "Jeu de données",
          "visualization" => "Visualisation de données",
          "pd" => "Divulgation proactive",
          "inventory" => "Répertoire de données ouvertes",
        );
        $ext_type_fr = $types_fr[$ext_type];

        $node = Node::create(['type' => 'external']);
        $node->set('title', $ext_type_en);
        $node->set('field_url', $url_en);
        $node->set('field_type', $ext_type);
        $node->set('field_uuid', $uuid);
        $node->status = 1;
        $node->enforceIsNew();
        $node->save();

        // create a translation for the node
        $node_fr = $node->addTranslation('fr');
        $node_fr->set('title', $ext_type_fr);
        $node_fr->set('field_url', $url_fr);
        $node_fr->set('field_type', $ext_type);
        $node_fr->set('field_uuid', $uuid);
        $node_fr->save();

        // call function for the new entity
        \Drupal::logger('external comment')->notice('New node of external content type ' . $ext_type . ' created with uuid ' . $uuid);
        return parent::getReplyForm($request, $node, $field_name, $pid);
      }
      else {
        \Drupal::logger('external comment')->warning('External comment posted for dataset with no uuid');
        return [];
      }
    }
    return parent::getReplyForm($request, $entity, $field_name, $pid);
  }

  /**
   * Validate function to check for certain conditions before rendering and posting comments
   * @param Request $request
   * @param $ext_type
   * @param $uuid
   * @return bool
   */
  public function validate(Request $request, $ext_type, $uuid) {

    // get url, type, uuid and domain of request object
    $referer_url = $request->headers->get('referer');
    $url_explode = explode("/",$referer_url);
    $referer_uuid = end($url_explode);
    $referer_type = prev($url_explode);
    $types = array("dataset", "visualization", "pd", "inventory");

    if ($referer_url) {
      $host_domain = $request->getSchemeAndHttpHost();
      $url_components = parse_url($referer_url);
      $referer_domain = $url_components['scheme'] . '://' . $url_components['host'];
      if ($url_components['port']) {
        $referer_domain .= ':' . $url_components['port'];
      }
    }

    // condition 1 - no url for referrer
    if ((empty($referer_url))) {
      \Drupal::logger('external comment')->warning('No referrer found for external comment');
      return false;
    }

    // condition 2 - domain name of both request and referrer are different
    elseif ($host_domain != $referer_domain) {
      \Drupal::logger('external comment')->warning('Host domain name and referrer domain name do not match');
      return false;
    }

    // condition 3 - external type has no value
    elseif (empty ($ext_type)){
      \Drupal::logger('external comment')->warning('No type given for external comment');
      return false;
    }

    // condition 4 - invalid type
    elseif (($ext_type != $referer_type) || !(in_array($ext_type, $types))) {
      \Drupal::logger('external comment')->warning('Invalid external application type');
      return false;
    }

    // condition 5 - uuid has no value
    elseif (empty ($uuid)){
      \Drupal::logger('external comment')->warning('No uuid given for external comment');
      return false;
    }

    // condition 6 - invalid uuid
    elseif (($uuid != $referer_uuid) || strlen($uuid) != 36) {
      \Drupal::logger('external comment')->warning('Invalid dataset UUID');
      return false;
    }

    return true;
  }

}
