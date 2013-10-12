<?php

class template_data {
  var $doc;

  function template_data () {
     $this->doc = new DomDocument('1.0');
  }

  function startSection($section_name, $parent = '') {
    $section = $this->doc->createElement($section_name);
    if ($parent == '')
      return $this->doc->appendChild($section);
    else
      return $parent->appendChild($section);
  }

  function setElement($element_name, $element_value, $parent) {
     $element = $this->doc->createElement($element_name);
     $element = $parent->appendChild($element);
     $value = $this->doc->createTextNode($element_value);
     $value = $element->appendChild($value);
  }

  function setElements($element_array, $parent) {
    while (list($key, $val) = each($element_array) ) {
      if (!is_numeric($key )) {
        $element = $this->doc->createElement(strtolower($key));
        $element = $parent->appendChild($element);
        $value = $this->doc->createTextNode($val);
        $value = $element->appendChild($value);
      }
    }
  }

  function getXMLString() {
     return $this->doc->saveXML(); 
  }
}

?>