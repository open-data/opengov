<?php

namespace Drupal\voting_webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use Drupal\webform\Entity\Webform;

/**
 * Class VotingWebformController.
 */
class VotingWebformController extends ControllerBase {

  /**
   * @param Request $request
   * @param $webform_id
   * @param null $uuid
   * @return Response
   */
  public function getAverageVote(Request $request, $uuid) {
    $renderHTML = '';
    $uuid = explode('?' , $uuid);
    $uuid = $uuid[0];

    // only display the results if validated
    if ($this->validate($request, $uuid, 'Vote-Rating (external)')) {
      // get average vote result for uuid
      try {

        // get current vote count and average
        $connection = \Drupal::database();
        $query = $connection->select('external_rating', 'v');
        $query->condition('v.uuid', $uuid, '=');
        $query->fields('v', ['vote_count', 'vote_average']);
        $result = $query->execute();
        $vote_average = 0;

        foreach ($result as $record) {
          $vote_average = round($record->vote_average);
        }

        switch ($vote_average) {
          case '1':
            $renderHTML .= '<img class="image-actual" alt="This dataset is currently ranked one star" src="/modules/custom/voting_webform/images/onestar.png">';
            break;
          case '2':
            $renderHTML .= '<img class="image-actual" alt="This dataset is currently ranked two star" src="/modules/custom/voting_webform/images/twostar.png">';
            break;
          case '3':
            $renderHTML .= '<img class="image-actual" alt="This dataset is currently ranked three star" src="/modules/custom/voting_webform/images/threestar.png">';
            break;
          case '4':
            $renderHTML .= '<img class="image-actual" alt="This dataset is currently ranked four star" src="/modules/custom/voting_webform/images/fourstar.png">';
            break;
          case '5':
            $renderHTML .= '<img class="image-actual" alt="This dataset is currently ranked five star" src="/modules/custom/voting_webform/images/fivestar.png">';
            break;
          case '6':
            $renderHTML .= '<img class="image-actual" alt="This dataset is currently ranked five star" src="/modules/custom/voting_webform/images/fivestar.png">';
            break;
          default :
            $renderHTML .= '<img class="image-actual" alt="This dataset is currently unrated" src="/modules/custom/voting_webform/images/zerostar.png">';
            break;
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('vote')->warning('Vote-Rating (external): Exception thrown while trying to get average vote with uuid: ' . $uuid);
        $renderHTML .= '<img class="image-actual" alt="This dataset is currently unrated" src="/modules/custom/voting_webform/images/zerostar.png">';
      }
    }

    // return response with HTML
    return new Response($renderHTML);
  }

/*
  public function getRatingExternalForm(Request $request, $uuid)  {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $action = '/'. $langcode . '/vote?uuid=' . $uuid;
    $renderHTML = '';

    $vote_webform = [
      '#type' => 'webform',
      '#webform' => 'vote',
      '#default_data' => ['dataset_uuid' => $uuid],
    ];
    $vote_webformHTML = \Drupal::service('renderer')->render($vote_webform);
    $vote_webformHTML = str_replace('form_action_p_pvdeGsVG5zNF_XLGPTvYSKCf43t8qZYSwcfZl2uzM', $action, $vote_webformHTML);

    $renderHTML .= '<link rel="stylesheet" media="all" href="/modules/custom/voting_webform/css/rating.css">';
    $renderHTML .= '<link rel="stylesheet" media="all" href="https://cdn.jsdelivr.net/gh/gjunge/rateit.js@1.1.2/scripts/rateit.css">';
    $renderHTML .= '<link rel="stylesheet" media="all" href="/modules/contrib/webform/css/webform.element.rating.css">';
    $renderHTML .= $vote_webformHTML;
    $renderHTML .= '<script src="https://cdn.jsdelivr.net/gh/gjunge/rateit.js@1.1.2/scripts/jquery.rateit.min.js"></script>';
    $renderHTML .= '<script src="/modules/contrib/webform/js/webform.element.rating.js?v=8.7.5"></script>';
    $renderHTML .= '<script src="/core/assets/vendor/jquery/jquery.min.js?v=3.2.1"></script>';

    // highlighted block or messaging
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $plugin_block = $block_manager->createInstance('system_messages_block', $config);
    $access_result = $plugin_block->access(\Drupal::currentUser());
    $messages = is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result
      ? []
      : $plugin_block->build();
    $messagesHTML = \Drupal::service('renderer')->render($messages);
    $renderHTML .= $messagesHTML;

    return new Response($renderHTML);
  }
*/
  public function validate(Request $request, $uuid, $type) {
    // get url, uuid and domain of request object
    $referer_url = $request->headers->get('referer');
    $url_explode = explode("/",$referer_url);
    $referer_uuid = end($url_explode);
    $referer_uuid = explode('?' , $referer_uuid);
    $referer_uuid = $referer_uuid[0];

    if ($referer_url) {
      $host_domain = $request->getHttpHost();
      $url_components = parse_url($referer_url);
      $referer_domain = $url_components['host'];
      if (array_key_exists('port', $url_components)) {
        $referer_domain .= ':' . $url_components['port'];
      }
    }

    // condition 1 - no url for referrer
    if ((empty($referer_url))) {
      \Drupal::logger('vote')->warning($type . ': No referrer found for vote');
      return false;
    }

    // condition 2 - domain name of both request and referrer are same
    elseif ($host_domain != $referer_domain) {
      \Drupal::logger('vote')->warning($type. ': Host domain name and referrer domain name do not match');
      return false;
    }

    // condition 3 - uuid has a value
    elseif (empty ($uuid)){
      \Drupal::logger('vote')->warning($type. ': No uuid given for vote');
      return false;
    }

    // condition 4 - invalid uuid
    elseif (($uuid != $referer_uuid) || (strlen($uuid) != 36 && strlen($uuid) != 32)) {
      \Drupal::logger('vote')->warning($type . ': Invalid UUID');
      return false;
    }

    return true;
  }
}
