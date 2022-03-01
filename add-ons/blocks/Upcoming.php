<?php
namespace Schedule\Addons;

class Upcoming extends Block {

    public $blocks = [
        "upcoming"
    ];

	/**
	 * Undocumented function
	 *
	 * @param [type] $attributes
	 * @param [type] $content
	 * @param [type] $full_data
	 * @return string
	 */
    public function render($attributes, $content, $full_data) : string {
        $block_id = uniqid();
        $result = "<div class='events-upcoming-block' data-id='" . $block_id . "'></div>";
		$result .= "<script>";
		$result .= "if (typeof document.event_block_data === 'undefined') { document.event_block_data = {}; }";
		$result .= "document.event_block_data['" . $block_id . "']=" . json_encode($attributes) . "</script>";
		return $result;
        
    }

}
