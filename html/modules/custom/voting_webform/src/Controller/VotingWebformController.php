<?php

namespace Drupal\voting_webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
  public function getAverageVote(Request $request, $webform_id, $uuid) {
    $renderHTML = '';

     // 1. get url, uuid and domain of request object
    $referer_url = $request->headers->get('referer');
    $url_explode = explode("/",$referer_url);
    $referer_uuid = end($url_explode);

    if ($referer_url) {
      $host_domain = $request->getSchemeAndHttpHost();
      $url_components = parse_url($referer_url);
      $referer_domain = $url_components['scheme'] . '://' . $url_components['host'];
      if ($url_components['port']) {
        $referer_domain .= ':' . $url_components['port'];
      }
    }

    // 2. only display the result if following conditions are not met

    // condition 1 - no url for referrer
    if ((empty($referer_url))) {
      \Drupal::logger('vote')->warning($webform_id . ': No referrer found for vote');
    }

    // condition 2 - domain name of both request and referrer are same
    elseif ($host_domain != $referer_domain) {
      \Drupal::logger('vote')->warning($webform_id . ': Host domain name and referrer domain name do not match');
    }

    // condition 3 - uuid has a value
    elseif (empty ($uuid)){
      \Drupal::logger('vote')->warning($webform_id . ': No uuid given for vote');
    }

    // condition 4 - invalid uuid
    elseif (($uuid != $referer_uuid) || strlen($uuid) != 36) {
      \Drupal::logger('vote')->warning($webform_id . ': Invalid UUID');
    }

    else {
      // 3. get average vote result for this uuid
      try {
        $connection = \Drupal::database();
        $query = $connection->select('webform_submission_data', 't1');
        $query->join('webform_submission_data', 't2', 't1.sid = t2.sid');
        $query
          ->fields('t2', ['value'])
          ->condition('t1.webform_id', $webform_id)
          ->condition('t1.name', 'dataset_uuid')
          ->condition('t1.value', $uuid)
          ->condition('t2.name', 'rating');
        $result = $query->execute();

        $vote_sum= 0;
        $vote_count=0;

        while ($record = $result->fetchAssoc()) {
          $vote_sum += $record['value'];
          $vote_count ++;
        }

        $vote_average = $vote_count != 0 ? ceil($vote_sum / $vote_count) : 0;

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
      catch (Exception $e) {
        \Drupal::logger('vote')->warning($webform_id . ': Exception thrown while trying to get average vote with uuid: ' . $uuid);
        $renderHTML .= '<img class="image-actual" alt="This dataset is currently unrated" src="/modules/custom/voting_webform/images/zerostar.png">';
      }
    }

    // 4. return response with HTML
    return new Response($renderHTML);
  }

}
