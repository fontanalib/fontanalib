<?php

namespace Drupal\catalog_importer\Feeds\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\catalog_importer\Feeds\Item\CatalogItem;
use Drupal\feeds\Result\ParserResult;

/**
 * Your Class Description.
 *
 * @FeedsParser(
 *   id = "marc_record_parser",
 *   title = @Translation("MARC Record Parser"),
 *   description = @Translation("Parser for catalog item records in MARC format")
 * )
 */
class MarcRecordParser extends PluginBase implements ParserInterface {

   /**
   * Hexadecimal value for Subfield indicator
   */
  private $subfield_indicator = "\x1F";

  /**
   * Hexadecimal value for End of Field
   */
  private $field_end = "\x1E";

  /**
   * Hexadecimal value for End of Record
   */
  private $record_end = "\x1D";


  private $author_fields = array('700', '710', '100', '110', '111', '711', '720', '264'); //'730','740','751', '752', '753', '754', 
  private $series_fields = array('440', '490', '810', '811', '830');
  private $title_fields = array('245', '130', '210', '222', '240', '242', '243', '246', '247', '730', '740'); //'240', '246', '247', '440', '490', '500', '505', '700', '710', '711', '730', '740', '780', '800', '810', '811', '830', '840'
  private $subject_fields = array('600', '610', '611', '630', '648', '650', '651', '653', '654', '655', '656', '657', '658', '662', '690', '691', '692', '693', '694', '695', '696', '697', '698', '699');
  private $audience_fields = array('521','385');

  private $form_codes = array(
    'a' => array(
      'type' => 'map',
      'mat_code'  => 1,
      'd' => 'Atlas',
      'g' => 'Diagram',
      'j' => 'Map',
      'k' => 'Profile',
      'q' => 'Model',
      'r' => 'Remote-sensing image',
      's' => 'Section',
      'u' => 'Unspecified',
      'y' => 'View',
      'z' => 'Other',
    ),
    'c' => array(
      'type' => 'electronic resource',
      'mat_code'  => 1,
      'a' => 'Tape cartridge',
      'b' => 'Chip cartridge',
      'c' => 'Computer optical disc cartridge',
      'd' => 'Computer disc, type unspecified',
      'e' => 'Computer disc cartridge, type unspecified',
      'f' => 'Tape cassette',
      'h' => 'Tape reel',
      'j' => 'Magnetic disk',
      'k' => 'Computer card',
      'm' => 'Magneto-optical disc',
      'o' => 'Optical disc',
      'r' => 'Remote',
      's' => 'Standalone device',
      'u' => 'Unspecified',
      'z' => 'Other',
    ),
    'd' => array(
      'type' => 'globe',
      'mat_code'  => 1,
      'a' => 'Celestial globe',
      'b' => 'Planetary or lunar globe',
      'c' => 'Terrestrial globe',
      'e' => 'Earth moon globe',
      'u' => 'Unspecified',
      'z' => 'Other',
    ),
    'f' => array(
      'type' => 'tactile material',
      'mat_code'  => 1,
      'a' => 'Moon',
      'b' => 'Braille',
      'c' => 'Combination',
      'd' => 'Tactile, with no writing system',
      'u' => 'Unspecified',
      'z' => 'Other',
    ),
    'g' => array(
      'type' => 'projected graphic',
      'mat_code'  => 1,
      'c' => 'Filmstrip cartridge',
      'd' => 'Filmslip',
      'f' => 'Filmstrip, type unspecified',
      'o' => 'Filmstrip roll',
      's' => 'Slide',
      't' => 'Transparency',
      'u' => 'Unspecified',
      'z' => 'Other',
    ),
    'h' => array(
      'type' => 'microform',
      'mat_code'  => 1,
      'a' => 'Aperture card',
      'b' => 'Microfilm cartridge',
      'c' => 'Microfilm cassette',
      'd' => 'Microfilm reel',
      'e' => 'Microfiche',
      'f' => 'Microfiche cassette',
      'g' => 'Microopaque',
      'h' => 'Microfilm slip',
      'j' => 'Microfilm roll',
      'u' => 'Unspecified',
      'z' => 'Other',
    ),
    'k' => array(
      'type' => 'nonprojected graphic',
      'mat_code'  => 1,
      'a' => 'Activity card',
      'c' => 'Collage',
      'd' => 'Drawing',
      'e' => 'Painting',
      'f' => 'Photomechanical print',
      'g' => 'Photonegative',
      'h' => 'Photoprint',
      'i' => 'Picture',
      'j' => 'Print',
      'k' => 'Poster',
      'l' => 'Technical drawing',
      'n' => 'Chart',
      'o' => 'Flash card',
      'p' => 'Postcard',
      'q' => 'Icon',
      'r' => 'Radiograph',
      's' => 'Study print',
      'u' => 'Unspecified',
      'v' => 'Photograph, type unspecified',
      'z' => 'Other',
    ),
    'm' => array(
      'type' => 'motion picture',
      'mat_code'  => 1,
      'c' => 'Film cartridge',
      'f' => 'Film cassette',
      'o' => 'Film roll',
      'r' => 'Film reel',
      'u' => 'Unspecified',
      'z' => 'Other'
    ),
    'o' => array(
      'type' => 'kit',
      'mat_code'  => null,
    ),
    'q' => array(
      'type' => 'notated music',
      'mat_code'  => null,
    ),
    'r' => array(
      'type' => 'remote-sensing image',
      'mat_code'  => null,
    ),
    's' => array(
      'type' => 'sound recording',
      'mat_code'  => 1,
      'b' => 'Belt',
      'd' => 'Sound disc',
      'e' => 'Cylinder',
      'g' => 'Sound cartridge',
      'i' => 'Sound-track film',
      'q' => 'Roll',
      'r' => 'Remote',
      's' => 'Sound cassette',
      't' => 'Sound-tape reel',
      'u' => 'Unspecified',
      'w' => 'Wire recording',
      'z' => 'Other'
    ),
    't' => array(
      'type' => 'text',
      'mat_code'  => 1,
      // 'a' => 'Regular Print',
      'b' => 'Large Print',
      'c' => 'Braille',
      'd' => 'Loose Leaf',
      // 'u' => 'Unsepcified',
      // 'z' => 'Other'
    ),
    'v' => array(
      'type' => 'videorecording',
      'mat_code'  => 4,
      'a' => 'Beta (1/2 in., videocassette)',
      'b' => 'VHS (1/2 in., videocassette)',
      'c' => 'U-matic (3/4 in., videocasstte)',
      'd' => 'EIAJ (1/2 in., reel)',
      'e' => 'Type C (1 in., reel)',
      'f' => 'Quadruplex (1 in. or 2 in., reel)',
      'g' => 'Laserdisc',
      'h' => 'CED (Capacitance Electronic Disc) videodisc',
      'i' => 'Betacam (1/2 in., videocassette)',
      'j' => 'Betacam SP (1/2 in., videocassette)',
      'k' => 'Super-VHS (1/2 in., videocassette)',
      'm' => 'M-II (1/2 in., videocassette)',
      'o' => 'D-2 (3/4 in., videocassette)',
      'p' => '8 mm.',
      'q' => 'Hi-8 mm.',
      's' => 'Blu-ray',
      // 'u' => 'Uknown',
      'v' => 'DVD',
      // 'z' => 'Other'
    ),//'videorecording',
    'z' => array(
      'type' => 'unspecified',
      'mat_code'  => 1,
      'm' => 'Multiple Physical Forms',
      'u' => 'Unspecified',
      'z' => 'Other'
    ),
  );
  private $resource_types = array(
    'a' =>'text',
    'c' =>'notated music',
    'd' =>'notated music',
    'e' =>'cartographic',
    'f' =>'cartographic',
    'g' =>'moving image',
    'i' =>'sound recording-nonmusical',
    'j' =>'sound recording-musical',
    'k' =>'still image',
    'm' =>'software, multimedia',
    'o' => 'kit',
    'p' =>'mixed material',
    'r' =>'three dimensional object',
    't' =>'text',
  );
  // 008/24-27 - Nature of contents (006/07-10)
  private $textContent = array(
    'a' => 'Abstract',
    'b' => 'Bibliography',
    'c' => 'Catalog',
    'd' => 'Dictionary',
    'e' => 'Encyclopedia',
    'f' => 'Handbook',
    'g' => 'Legal article',
    'i' => 'Index',
    'j' => 'Patent document',
    'k' => 'Discography',
    'l' => 'Legislation',
    'm' => 'Thesis',
    'n' => 'Surveys of literature in a subject area',
    'o' => 'Review',
    'p' => 'Programmed text',
    'q' => 'Filmography',
    'r' => 'Directory',
    's' => 'Statistics',
    't' => 'Technical report',
    'u' => 'Standards/specification',
    'v' => 'Legal cases and case notes',
    'w' => 'Law reports and digests',
    'y' => 'Yearbook',
    'z' => 'Treaty',
    '2' => 'Offprint',
    '5' => 'Calendar',
    '6' => 'Comics/graphic novel',
);

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $result = new ParserResult();
    $raw = $fetcher_result->getRaw();
    $marc_records = explode($this->record_end, $raw);

    foreach ($marc_records as $record) {
      $marc_record = $this->getMarcFields($record);

      if(empty($marc_record['leader'])){
        continue;
      }
      \Drupal::logger('catalog_importer')->notice('RECORD: <pre>@exclude</pre>', array(
        '@exclude'  => print_r($marc_record, TRUE),
      )); 
      $item = $this->mapMarcFields($marc_record);
      $result->addItem($item);
    }

    return $result;
  }
/**
   * Parse all of the items from the MARC record
   */
  private function getMarcFields($marc_record){
    $marc_field_values = explode($this->field_end, $marc_record);
  
    // Now this is harder, we need to break the leader from the directory
    $start_length = strlen($marc_field_values[0]);
    $leader = substr($marc_field_values[0], 0, 23);
    if(!$leader || empty($leader)){
      return null;
    }
    $directory = substr($marc_field_values[0], 24, $start_length);
    $marc_field_values[0] = $leader;

    // Get the field numbers from the directory
    // $directoryfields contaings the fieldname, start position and length
    // that will get taken care of in a second
    $directory_fields = str_split($directory, 12);

    //Start building $record array
    $record = array();
    //First we set the leader and take the leader value out of the values
    $record['leader'] = $leader;
    array_splice($marc_field_values, 0, 1);

    $marc_field_count = array();
    //Then we loop through the directory fields and build array
    foreach ($directory_fields AS $key => $directory_field) {
      
      //The marc field number is just the first 3 characters
      $field_number = substr($directory_field, 0, 3);
      //The field value is the correpsonding value in the marcfieldvalues array.
      $field_value = $marc_field_values[$key];
      
      //We need to keep track of the field iterations
      if (!isset($marc_field_count[$field_number]) || !$marc_field_count[$field_number]){
        $marc_field_count[$field_number] = 0;
      }
      $field_count = $marc_field_count[$field_number];
      if (substr($directory_field, 0, 2) == '00') {
        // Populate Control fields
        // Technically control fields can be repeated, not that I ever see it.
        $record[$field_number][$field_count] = $field_value;
      }
      else {
        //Populate Indicators
        //$record[$field_number][$field_count]['field'] = $field_value;
        $record[$field_number][$field_count]['i1'] = substr($field_value, 0, 1);
        $record[$field_number][$field_count]['i2'] = substr($field_value, 1, 1); 
        //Start work on subfields
        // US (char) 31(dec) 1F 037 - character used to seperate subfields
        $subfields = explode($this->subfield_indicator, $marc_field_values[$key]);
        
        //Get rid of indicators
        array_splice($subfields, 0, 1);

        //Insert subfields in database
        foreach ($subfields as $subfield) {
          $subfield_code = substr($subfield, 0, 1);
          $subfield_value = substr($subfield, 1, (strlen($subfield)-1));
          $record[$field_number][$field_count][$subfield_code] = $subfield_value;
        }
      }
      //We need to keep track of the field iterations
      $marc_field_count[$field_number]++;
    }
    return $record;
  }

  private function mapMarcFields($marc_record){
    //dd($marc_record);
    $record = array(
      'creators'        => [
        'names'=>array(),
        'roles'=>array()
      ], 
      'description'     => array(),
      'cover'           => '',
      'topics'          => array(), // 650
      'genre'           => array(), // 655
      'audience'        => array(),
      'titles'          => array(),
      'title'           => '',
      'type'            => '',
      'form'            => array(),
      'classification'  => array(),
      'identifier_ids' => array(),
      'identifier_types'=> array(),
      'item_creators'   =>array()
    );
    $catalog_item = new CatalogItem();
    $leader = isset($marc_record['leader']) ? str_split($marc_record['leader']) : null;
    $f007 = isset($marc_record['007']) && !is_array($marc_record['007']) ? array(str_split($marc_record['007'])) : isset($marc_recotd['007']) ? array_map(function($f){
      return str_split($f);
    }, $marc_record['007']) : null;
    $f008 = isset($marc_record['008']) && !is_array($marc_record['008']) ? array(str_split($marc_record['008'])) : isset($marc_record['008']) ? array_map(function($f){
      return str_split($f);
    }, $marc_record['008']) : null;
            // $f008 = null;
            // if(isset($marc_record['008'])){
            //   $f008 = array();
            //   if(!is_array($marc_record['008'])){
            //     $f008[] = str_split($marc_record['008']); 
            //   } else{
            //     foreach($marc_recotd['008'] as $i => )array_map(function($f){
            //       return str_split($f);
            //     }, $marc_record['008']) :)
            //   }

    if($leader){
      if(isset($this->resource_types[$leader[6]])){
        $catalog_item->set('type', $this->resource_types[$leader[6]]);
        $record['form'][] = $this->resource_types[$leader[6]];
      }
      if($leader[6] === 'a'){
        if (in_array((string) $leader[7], array('b', 'i', 's'))) {
          $record['form'][] = 'series';
        }
        if (in_array((string) $leader[7], array('a', 'c', 'd', 'm'))) {
          $record['form'][] = 'book';
        }
      }
    }

    if($f007){
      foreach($f007 as $v){
        if(isset($this->form_codes[$v[0]])){
          $record['form'][]=$this->form_codes[$v[0]]['type'];
          if($this->form_codes[$v[0]]['mat_code']){
            $format = $v[$this->form_codes[$v[0]]['mat_code']];
            if(isset($this->form_codes[$v[0]][$format])){
              $record['form'][]=$this->form_codes[$v[0]][$format];
            }
          }
        }
      }
    }
    if($f008){
      $date = array_slice($f008[0], 0, 6);
      /**
       * TO FIX : active date - change to 005 ? or date1 in 008 ?
       */
      $catalog_item->set('active_date', implode("", $date));
      $lang = implode("", array_slice($f008[0], 35, 3));//substr($marc_record['008'][0], 35, 3); 
      \Drupal::logger('catalog_importer')->notice("LANG- $lang: <pre>@exclude</pre>", array(
              '@exclude'  => print_r($f008, TRUE),
            )); 
      if($lang !== 'eng' && $lang !== 'und' && $lang !== 'zxx'){
        $record['genre'][] = 'foreign language';
      } 
      if(in_array('series', $record['form'])){
        switch($f008[21]){
          case 'm': break;
          case 'n': $record['form'][]='newspaper'; break;
          case 'p': $record['form'][]='periodical'; break;
        }
        if($leader[7] === 'a'){
          $record['form'][] = 'article';
        }
      } elseif (in_array('text', $record['form'])) {
        if (isset($this->textContent[$f008[0][24]])) {
            // Slight simplification
            $record['form'][] = $this->textContent[$f008[0][24]];
        }
        if ($leader[7] === 'a') {
            $material = 'Article';
            $record['form'][] = 'article';
        }
      }
    }
    foreach($marc_record as $field => &$info){
      $field_str = strval($field);
      switch($field_str){
        case 'leader': break;
        case '001':
          $catalog_item->set('guid', (string) $info[0]); 
          if(strtolower(substr($info[0], 0, 3)) !== 'kan'){
            $catalog_item->set('tcn', (string) $info[0]);
          } break;
        // case '005': 
        //   $catalog_item->set('active_date', substr($info[0], 0, 8)); break;
        case '007': break;
        case '008': break;
        case '010':
        case '020':
        case '022':
        case '024':
        // case '040': 
        case '060':
          // $f = intval(ltrim($field_str, '0'));
          // $t = gettype($f);
          // \Drupal::logger('catalog_importer')->notice("field: $f <pre>@exclude</pre>", array(
          //     '@exclude'  => print_r($t, TRUE),
          //   )); 
          // $type = $f === 10 ? 'lccn' : $f === 20 ? 'isbn' : $f === 22 ? 'issn' : $f === 60 ? 'nlm' : 'other';
          $type;
          switch($field_str){
            case '010': $type = 'lccn'; break;
            case '020': $type = 'isbn'; break;
            case '022': $type = 'issn'; break;
            case '060': $type = 'nlm'; break;
            default: $type = 'other';
          }
          foreach($info as $i => $v){
            if(isset($v['a']) && !empty($v['a'])){
              $record['identifier_ids'][] = $v['a'];
              $record['identifier_types'][] = $type;
              if($type === 'isbn'){
                $isbn = preg_replace('/^([0-9\-xX]+).*$/', '\1', $v['a']);
                if(!empty($isbn)){
                  $record['isbn'][] = $isbn;
                }
              }
            }
          } break;
        case '028':
          if(strtolower($info[0]['b']) === 'kanopy'){
            $record['cover'] = 'https://www.kanopy.com/sites/default/files/imagecache/vp_poster_small/video-assets/' . $info[0]['a'] . '_poster.jpg'; break;
          }
        case '080':
        case '082':
          foreach($info as $classification){
            if(isset($classification['a'])){
              $record['classification'][] = preg_replace('/^.*?([0-9.]+)\/?([0-9.]*).*$/', '\1\2', $classification['a']);
              $dewey_keywords = $this->keywordsFromClassification($classification['a']);
              $record['topics'][] = $dewey_keywords;
              $record['audience'][] = $dewey_keywords;
              $record['genre'][] = $dewey_keywords;
            }
          } break;
        case '856':
          foreach($info as $url){
            if(strpos($url['u'], '/external-image') !== FALSE){
              // $record['cover'] = $url['u'];
              $record['identifier_ids'][] = $url['u'];
              $record['identifier_types'][] = 'image url';
            }
            if($url['i2'] === 0 && $url['i1'] === 4 && stripos($url['z'], 'kanopy') !== FALSE){
              $catalog_item->set('tcn', substr($url['u'], strpos($url['u'], "kanopy.com/node/") + 1));
            }
            $record['identifier_ids'][] = $url['u'];
            $record['identifier_types'][] = 'url';
          } break;
        case '546':
          $info[0]['a'] = implode(", ", explode(",",$info[0]['a']));
        case '520':
          //$record['description'][] = $this->getFieldArray($info); break;
          array_unshift($record['description'], implode(';', $this->getFieldArray($info))); break;
        default:
          $k = intval(ltrim($field_str, '0'));
          if($k >= 100 && $field !== 'leader' && ($k < 300 || $k >= 400) && !in_array($k, $this->subject_fields)){
            $record['description'][] = implode("; ", $this->getFieldArray($info, " "));
          }
         
          if(in_array($field_str, $this->author_fields)){
            // \Drupal::logger('catalog_importer')->notice("CREATOR- $field: <pre>@exclude</pre>", array(
            //   '@exclude'  => print_r($info, TRUE),
            // )); 
            $record['creators'] = $this->getCreators($field, $info, $record['creators']);
          }
          if(in_array($field_str, $this->title_fields)){
            foreach($info as $i => $v){
              $title = $this->getStringField($v, $field_str);
              if(!empty($title)){
                if(empty($record['title']) && in_array($field_str, ['245', '130'])){
                  $record['title'] = $name = trim(preg_replace('/\[.*\]/',"", $title),"= / . : , ; ");
                }
                $record['titles'][]=$title;
              }
            }
          }
          if(in_array($field_str,$this->audience_fields)){
            $s = $this->getFieldArray($info, null, true);
            if(!empty($s)){
              $record['audience'] = array_merge($record['audience'], $s);
            }
          }
          if(in_array($field_str,$this->subject_fields)){
            $s = $this->getFieldArray($info, null, true);
            if(!empty($s)){
              $record['topics'] = array_merge($record['topics'], $s);
              $record['audience'] = array_merge($record['audience'], $s);
              if($field_str === '655'){ //|| $field == 653
                $record['genre'] = array_merge($record['genre'], $s);
              }
            }
          }
      }
    }

    $roles = !empty($record['creators']['roles']) ? array_map(function($role_array){
      return implode(", ", $role_array);
    }, $record['creators']['roles']) : array();
    $identifiers = array_combine($record['identifier_ids'], $record['identifier_types']);
    foreach($identifiers as $id => $t){
      $record['ids'][]= array(
        'field_identifier_id' => $id,
        'field_identifier_type' => $t
      );
    }
    $creators = array_combine($record['creators']['names'], $record['creators']['roles']);
    foreach($creators as $name => $role){
      $record['item_creators'][]= array(
        'field_creator_name' => $name,
        'field_creator_role' => $role
      );
    }
    // $record = array(
    //   'creators'        => [
    //     'names'=>array(),
    //     'roles'=>array()
    //   ], 
    //   'description'     => array(),
    //   'urls'            => array(), // 856[0][u]
    //   'cover'           => '',
    //   'topics'          => array(), // 650
    //   'genre'           => array(), // 655
    //   'audience'        => array(),
    //   'titles'          => array(),
    //   'title'           => '',
    //   'type'            => '',
    //   'form'            => array(),
    //   'classification'  => array(),
    //   'identifier_ids' => array(),
    //   'identifier_types'=> array(),
    //   'isbn'            =>array(),
    //   'ids'             =>array(),
    //   'item_creators'   =>array()
    // );

    $catalog_item->set('alt_titles', $record['titles'])
      ->set('identifier_types', $record['identifier_types'])
      ->set('identifier_ids', $record['identifier_ids'])
      ->set('isbn', $record['isbn'])
      ->set('audience', $record['audience'])
      ->set('genre', $record['genre'])
      ->set('topics', $record['topics'])
      ->set('item_creators', $record['item_creators'])
      ->set('creators', $record['creators']['names'])
      ->set('roles', $roles)
      ->set('image', $record['cover'])
      ->set('form', $record['form'])
      ->set('classification', $record['classification'])
      ->set('description', implode("<br/><br/>", array_filter($record['description'])));
      //->set('roles', $record['creators']['roles'])
    if(empty($record['title'])){
      $catalog_item->set('title', $record['titles'][0]);
    } else {
      $catalog_item->set('title', $record['title']);
    }

    \Drupal::logger('catalog_importer')->notice('ITEM: <pre>@exclude</pre>', array(
      '@exclude'  => print_r($catalog_item, TRUE),
    )); 
    return $catalog_item;
  }
  /**
   * Function to retrieve the creator fields from a record
   */
  private function getCreators($field, $info, &$creators){
    $k = (string) substr($field, -2);

    foreach($info as $i => $val){
      $name = '';
      $roles = array();
      foreach($val as $indicator => $v){
        $indicator = strval($indicator);
        switch($k){
          case '10':
            if($indicator === 'a'){
              $name = $v; break;
            }
            if($indicator === 'b' && empty($name)){
              $name = $v; break;
            }
            if($indicator === '4' || $indicator === 'e'){
              $roles[]=trim($v, ",;.: "); break;
            } break;
          case '11':
            if($indicator === 'a'){
              $name = $v; break;
            }
            if($indicator === 'e' && empty($name)){
              $name = $v; break;
            }
            if($indicator === '4' || $indicator === 'j' || $indicator === 'i'){
              $roles[]=trim($v, ",;.: "); break;
            } break;
          case '20':
            if($indicator === 'a'){
              $name = $v; break;
            }
            if($indicator === 'e' && empty($name)){
              $name = $v; break;
            }
            if($indicator === '4' || $indicator === 'b' || $indicator === 'e' || $indicator === 'c' || $indicator === '6'){
              $roles[]= trim($v, ",;.: "); break;
            } break;
          case '64':
            if($indicator === 'b'){
              if(strpos(strtolower($val['b']), 'great courses') !== FALSE){
                $name ="The Great Courses"; break;
              } elseif(empty($name)) {
                $name = $v; break;
              }
            }
            if($indicator === '4' || $indicator === 'e' || $indicator === '6'){
              $roles[]=trim($v, ",;.: "); break;
            } break;
          default:
            if($indicator === 'a'){
              $name = $v; break;
            }
            if($indicator === 'q' && empty($name)){
              $name = $v; break;
            }
            if($indicator === 'b' || $indicator === 'e' || $indicator ==='c' || $indicator === '4' || $indicator === '6'){
              $roles[]=trim($v, ",;.: ");
            }
        }
      }

      $name = trim(preg_replace('/\(.*\)/',"",$name),"= / . : , ; ");
      $name = implode(", ", array_map(function($n){
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u','',$n);
      }, explode(",", $name)));

      $roles = array_unique(array_map('strtolower', $roles));

      if(!empty($name)){
        if(empty($roles)){
          $roles = $k === '64' ? ['prod/dst'] : ['creator'];
        }
        if(!in_array($name, $creators['names'])){
          if($k === '00'){
            array_unshift($creators['names'], $name);
            array_unshift($creators['roles'], $roles);
          } else {
          $creators['names'][] = $name;
          $creators['roles'][] = $roles;
          }
        } else {
          $key = array_search($name, $creators['names']);
          foreach($roles as $r){
            if(!in_array($r, $creators['roles'][$key])){
              $creators['roles'][$key][]=$r;
            }
          }
        }
      }
    }
    return $creators;
  }

  private function getStringField($array, $field_number, $separator = " ", $subfield=NULL){
    $field = array_merge(array(), $array);
    if(empty($field)){
      return null;
    }

    foreach($field as $f){
      if(isset($f['i1'])){
        unset($f['i1']);
      }
      if(isset($f['i2'])){
        unset($f['i2']);
      }
    }
    
    $value='';

    if($subfield){
      $value = (string) $field[$subfield];
    } elseif($field_number === '700'){
      $value = $field['a'];
    } elseif($field_number === '710'){
      $value = isset($field['a']) ? $field['a'] : implode($separator, $field);
    } elseif($field_number === '264'){
      $value = isset($field['b']) ? $field['b']: implode($separator, $field);
    } elseif($field_number === '245'){
      $value = isset($field['a']) ? $field['a'] : implode($separator, $field);
    } else{
      $value = implode($separator, $field);
    }
    $value = trim($value);
    return rtrim($value, ",;.:");
  }

  private function getFieldArray($array, $separator = null, $strip_numeric = false){
    $field = array_merge(array(), $array);
    $value = array();

    if(isset($field['i1'])){
      unset($field['i1']);
    }
    if(isset($field['i2'])){
      unset($field['i2']);
    }

    foreach($field as $f){
      if(is_array($f)){
        if(isset($f['i1'])){
          unset($f['i1']);
        }
        if(isset($f['i2'])){
          unset($f['i2']);
        }
        if($separator){
          $val = implode($separator, $f);
          $value[]=$val;
        } else if(!$strip_numeric){
          $value = array_merge($value, $f);
        } else {
          foreach($f as $k=>$v){
            if(!is_numeric($k)){
              $value[]=$v;
            }
          }
        }
      } else {
        $value[] = $f;
      }
    }
    return $value;
  }
  public function keywordsFromClassification($classification, $dewey = NULL){
    if($dewey && is_numeric($dewey) &&  $dewey > 0 && $dewey < 1000){
      $dNum = $dewey;
      $dText = '';
    } else{
      $dNum = preg_replace('/[^ .0-9]/', '', $classification);
      $dNum = trim($dNum, ". ");
      $dNum = floatval($dNum);
      $dText = preg_replace('/[\d\/\\.\[\]]/', ' ', $classification);
      $dText = strtolower(trim($dText));
    }
    if ($dNum > 0) {
      if ($dNum < 742 && $dNum > 739){
        return "graphic novel";
      } elseif (($dNum >= 800 && $dNum <= 899)) { 
        return "fiction";
      } elseif (($dNum >= 791 && $dNum < 793)) {
        return "video";
      } elseif(($dNum > 919 && $dNum < 921) || ($dNum > 758 && $dNum < 760) || ($dNum > 708 && $dNum < 710) || ($dNum > 608 && $dNum < 610) || ($dNum > 508 && $dNum < 510) || ($dNum > 408 && $dNum < 410) || ($dNum > 269 && $dNum < 271) || ($dNum > 108 && $dNum < 110)){
        return 'biography';
      } elseif( ($dNum > 810 && $dNum < 812) || ($dNum > 820 && $dNum < 822) || ($dNum > 830 && $dNum < 832) || ($dNum > 840 && $dNum < 842) || ($dNum > 850 && $dNum < 852) || ($dNum > 860 && $dNum < 862) || ($dNum > 870 && $dNum < 875)  || ($dNum > 880 && $dNum < 885)){
        return 'poetry';
      } elseif (($dNum >= 780 && $dNum < 788)) {
        return "music";
      } elseif(($dNum < 770 || $dNum >= 900) && $dNum < 1000){
        return 'nonfiction';
      }
    } elseif ($dText == "b" || strpos($dText, 'biography') !== FALSE) {
        return "biography";
    } elseif ($dText== "e" || strpos($dText, 'easy') !== FALSE || strpos($dText, 'picture book') !== FALSE) {
        return "easy";
    } elseif(substr($dText,0,3) === 'cd ' || strpos($dText, 'music') !== FALSE) {
        return "music";
    } elseif ((substr($dText,0,3) === 'fic' || strpos($dText, 'fiction') !== FALSE) && (strpos($dText, 'film') === FALSE || strpos($dText, 'video') === FALSE || strpos($dText, 'television') === FALSE)){
        return "fiction";
    }
    return $dText;

  }
  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [
      'guid' => [
        'label' => $this->t('GUID'),
        'description' => $this->t('Unique ID for Feeds.'),
      ],
      'title' => [
        'label' => $this->t('Title'),
        'description' => $this->t('Resource Title'),
      ],
      'active_date' => [
        'label' => $this->t('Active Date'),
        'description' => $this->t('Date item added to catalog.'),
      ],
      'alt_titles' => [
        'label' => $this->t('Alternative Titles'),
        'description' => $this->t('Other titles for this item.'),
      ],
      'audience' => [
        'label' => $this->t('Audience'),
        'description' => $this->t('Audience indicators'),
      ],
      'creators' => [
        'label' => $this->t('Creators'),
        'description' => $this->t('Resource creators/authors'),
      ],
      'roles' => [
        'label' => $this->t('Roles'),
        'description' => $this->t('Resource creators/authors roles'),
      ],
      'description' => [
        'label' => $this->t('Description'),
        'description' => $this->t('Resource abstract/description'),
      ],
      'featured_collection' => [
        'label' => $this->t('Featured Collection'),
        'description' => $this->t('Featured Collection Terms'),
      ],
      'genre' => [
        'label' => $this->t('Genre'),
        'description' => $this->t('Genre Indicators'),
      ],
      'topics' => [
        'label' => $this->t('Topics'),
        'description' => $this->t('Topic/Subject Indicators'),
      ],
      'image' => [
        'label' => $this->t('Image'),
        'description' => $this->t('url for resource image'),
      ],
      'tcn' => [
        'label' => $this->t('TCN'),
        'description' => $this->t('Record ID'),
      ],
      'identifier_ids' => [
        'label' => $this->t('Identifier IDs'),
        'description' => $this->t('item Record identifier IDs'),
      ],
      'identifier_types' => [
        'label' => $this->t('Identifier Types'),
        'description' => $this->t('item Record identifier types'),
      ],
      'type' => [
        'label' => $this->t('Resource Type'),
        'description' => $this->t('typeOfResouce'),
      ],
      'form' => [
        'label' => $this->t('Form'),
        'description' => $this->t('Item form.'),
      ],
      'classification' => [
        'label' => $this->t('Dewey'),
        'description' => $this->t('DDC'),
      ],
    ];
  }
}