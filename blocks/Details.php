<?php
namespace Contexis\Events\Blocks;

use Contexis\Events\Intl\PriceFormatter;
use Contexis\Events\Intl\DateFormatter;

class Details {

	public array $args;
	
	public $blockname = 'details';

    public static function init(Assets $assets) {

		$instance = new self;
        $instance->args = $assets->args;
		
		add_action('init', [$instance, 'register_block']);
        
       
    }

	public function get_block_meta() {
		$filename = EM_DIR . "/blocks/src/details/block.json";
		
		if(!file_exists($filename)) {    
			return false;
		}
		$string = file_get_contents($filename);
		
		return array_merge(json_decode($string, true), $this->args);
		
	}

	function register_block() {	
		$meta = $this->get_block_meta();
		$meta['render_callback'] = [$this,'render'];
		register_block_type($meta['name'], $meta);
	}     

	/**
	 * Undocumented function
	 *
	 * @param [type] $attributes
	 * @param [type] $content
	 * @param [type] $full_data
	 * @return string
	 */
    public function render($attributes, $content, $full_data) : string {
        $attributes['event'] = $this->get_event();
		//var_dump($attributes['event']);
		$attributes['date'] = DateFormatter::get_date($attributes['event']['start'], $attributes['event']['end']);
		$attributes['time'] = DateFormatter::get_time($attributes['event']['start'], $attributes['event']['end']);
		$attributes['currency'] = get_option('dbem_bookings_currency');
		
		$attributes['price'] = new PriceFormatter($attributes['priceOverwrite'] !== "" ? $attributes['priceOverwrite'] : $attributes['event']['price']);
		
		$attributes['is_daterange'] = wp_date('jY', $attributes['event']['start']) !== wp_date('jY', $attributes['event']['end']);
		$template = $this->get_template($full_data->name);
        
        return \Timber\Timber::compile($template, $attributes);

    }
    public function get_template($name) : string { 
        $filename = substr($name, strpos($name, "/")+1) . ".twig";
        
        if(file_exists(get_template_directory() . "/plugins/events/" . $filename)) {
            return get_template_directory() . 'events/' . $filename;
        }

        return EM_DIR . '/templates/blocks/' . $filename;
    }
    
    private function get_event() {

		$tax_query = [];

		global $post;
		return \EM_Events::get_rest(['post_id' => $post->id])[0];

    }

}
