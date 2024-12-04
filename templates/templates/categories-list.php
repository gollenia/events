<?php
/*
 * Default Categories List Template
 * This page displays a list of locations, called during the em_content() if this is an events list page.
 * You can override the default display settings pages by copying this file to yourthemefolder/plugins/events/templates/ and modifying it however you need.
 * You can display locations (or whatever) however you wish, there are a few variables made available to you:
 * 
 * $args - the args passed onto EM_Categories::output()
 * 
 */ 
$args = apply_filters('em_content_categories_args', $args);

echo EM_Categories::output( $args );