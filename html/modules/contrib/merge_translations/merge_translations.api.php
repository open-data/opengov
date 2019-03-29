<?php

/**
 * @param $node_array
 * Add ability to alter $node values before add to translation
 */
function hook_merge_translations_prepare_alter(&$node_array){
    $node_array['title'][0]['value'] = 'Translated title';
}