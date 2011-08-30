<?php
class postform{
	function html_form($type,$array,$id,$label=true,$legend=true,$blank_item=true,$fieldset=true,$inner_container=NULL){
	// could ditch the type specification and assume that if not provided we are making a checkbox, but if we want to handle other variables
	// we'll have to do something else to tell it its not a checkbox value
	// check array if assoc, if so then use the value as its label
		if(is_array($array))
			foreach($array as $loc=>$prop){
				$select = (!is_int($loc)? $loc: str_replace('_',' ',$prop));
				switch ($type) {
				case "option":
					$result.= '<option value="'.$prop.'" '.($_POST[$id] == $prop? " selected ":NULL).'>'. $select . '</option>';
					break;
				case "radio":
					$result .= '<input type="radio" name="'.$id.'" id="$id" value="'.$prop.'" '.($_POST[$id] == $prop ? ' checked ':NULL).' /> '.($label==true?'<label for= "'.$prop.'">'.$select.'</label>':NULL);
					break;
				}
				if($inner_container !=NULL && is_array($inner_container)) $result .= $inner_container[0] . $result . $inner_container[1];
				}
		elseif($type='checkbox' && is_string($array))
		// this can't use won't need an inner array... also support an array of checkboxes (given an assoc array)
		// if the count of the passed items is equal to the default then automatically set label/legend/blank item / fieldset to false
			 $result .= '<input type="checkbox" name="'.$id.'" id="'.$id.'" '.($_POST[$id] != 'on' ?  NULL :' checked ' ).'/>';
			// check box processing
			return ($fieldset!=true?NULL:'<fieldset id="'.$id.'">')
					.
					($legend!=true?NULL:'<legend>Show '.$id.' value</legend>')
					.
					($type=='option'?
					'<select title="'.$id.'" name="'.$id.'">'.
						($blank_item != true?NULL:'<option value ="">Select '.$id.'</option>') .$result . '</select>':$result)
					.
					($fieldset!=true?NULL:'</fieldset>');
	}
}