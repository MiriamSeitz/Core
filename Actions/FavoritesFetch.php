<?php namespace exface\Core\Actions;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class FavoritesFetch extends FavoritesAdd {
	
	protected function init(){
		parent::init();
	}	
	
	protected function perform(){
		$this->set_result($this->get_favorites_json());
	}
	
	protected function get_favorites_json(){
		$result = array();
		foreach ($this->get_context()->get_favorites_all() as $fav_list){
			$result[] = array(
					'object_id' => $fav_list->get_meta_object()->get_id(),
					'object_name' => $fav_list->get_meta_object()->get_name(),
					'instances' => $fav_list->export_uxon_object()
			);
		}
		return json_encode($result);
	}

}
?>