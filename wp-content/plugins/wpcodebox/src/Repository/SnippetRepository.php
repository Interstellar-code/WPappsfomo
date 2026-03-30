<?php

namespace Wpcb\Repository;


class SnippetRepository
{
    public function getQuickActionsSnippets()
    {
        global $wpdb;

        $snippets_added_to_quick_actions_query = "SELECT {$wpdb->posts}.* 
        FROM {$wpdb->posts}
        INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
        INNER JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id )  
        INNER JOIN {$wpdb->postmeta} AS mt2 ON ( {$wpdb->posts}.ID = mt2.post_id ) 
        WHERE 1=1 AND 
        (( {$wpdb->postmeta}.meta_key = 'wpcb_add_to_quick_actions' AND {$wpdb->postmeta}.meta_value = '1' ) AND 
        ( mt1.meta_key = 'wpcb_run_type' AND mt1.meta_value = 'once' ) AND
        ( mt2.meta_key = 'wpcb_code_type' AND mt2.meta_value = 'php' ) ) AND
        {$wpdb->posts}.post_type = '" . \Wpcb\Config::SNIPPET_POST_TYPE . "'  
        GROUP BY {$wpdb->posts}.ID ORDER BY {$wpdb->posts}.menu_order";


        return $wpdb->get_results($snippets_added_to_quick_actions_query);
    }

    public function getCustomSnippetsQuery()
    {
        global $wpdb;
        return

                "SELECT {$wpdb->posts}.* 
            FROM {$wpdb->posts}
            INNER JOIN {$wpdb->postmeta} 
            ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ) 
            INNER JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id ) 
            INNER JOIN {$wpdb->postmeta} AS mt2 ON ( {$wpdb->posts}.ID = mt2.post_id ) 
            INNER JOIN {$wpdb->postmeta} AS mt3 ON ( {$wpdb->posts}.ID = mt3.post_id ) 
            WHERE 1=1 AND 
            ( ( {$wpdb->postmeta}.meta_key = 'wpcb_enabled' AND {$wpdb->postmeta}.meta_value = '1' ) AND 
            ( mt1.meta_key = 'wpcb_run_type' AND mt1.meta_value = 'always' ) AND 
            (( mt2.meta_key = 'wpcb_where_to_run' AND mt2.meta_value = 'custom' )  OR 
            ( mt3.meta_key = 'wpcb_code_type' AND mt3.meta_value != 'php' ) )) AND 
            {$wpdb->posts}.post_type = '" . \Wpcb\Config::SNIPPET_POST_TYPE . "'  
            GROUP BY {$wpdb->posts}.ID ORDER BY {$wpdb->posts}.menu_order
            ";
    }

    public function getSnippetsQuery()
    {

        global $wpdb;

        return "SELECT {$wpdb->posts}.* 
            FROM {$wpdb->posts}
            INNER JOIN {$wpdb->postmeta} 
            ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ) 
            INNER JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id ) 
            INNER JOIN {$wpdb->postmeta} AS mt2 ON ( {$wpdb->posts}.ID = mt2.post_id ) 
            INNER JOIN {$wpdb->postmeta} AS mt3 ON ( {$wpdb->posts}.ID = mt3.post_id ) 
            WHERE 1=1 AND 
            ( ( {$wpdb->postmeta}.meta_key = 'wpcb_enabled' AND {$wpdb->postmeta}.meta_value = '1' ) AND 
            ( mt1.meta_key = 'wpcb_run_type' AND mt1.meta_value = 'always' ) AND 
            ( mt2.meta_key = 'wpcb_where_to_run' AND mt2.meta_value IN ('frontend','admin','everywhere') )  AND 
            ( mt3.meta_key = 'wpcb_code_type' AND mt3.meta_value = 'php' )) AND 
            {$wpdb->posts}.post_type = '" . \Wpcb\Config::SNIPPET_POST_TYPE . "'  
            GROUP BY {$wpdb->posts}.ID ORDER BY {$wpdb->posts}.menu_order
            ";
    }
}