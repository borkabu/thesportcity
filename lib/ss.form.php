<?php
// form definition file
$form_data = array(
  // public site
  'public' => array(
    'group_id' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'form',
				'options' => array(
          '[E]' => '',
          NEWS_DOMESTIC => 'Lietuva',
          NEWS_WORLD => 'Pasaulis'
        )
	)
    ),
    'query' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 15,
        'class' => 'form'
      )
    ),
    'param1' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'txtfld',
				'options' => array(
          '[E]' => '',
          '15-17' => '15-17',
          '18-19' => '18-19',
          '20-25' => '20-25',
          '26-30' => '26-30',
          '31-35' => '31-35',
          '36-40' => '36-40',
          '41-100' => 'virš 40'
        )
			)
		),
    'param2' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'txtfld',
				'options' => 'positions'
			)
		),
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 30,
        'maxlength' => 64,
        'class' => 'form'
      )
    ),
    'nick' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 16,
        'class' => 'form'
      )
    ),
    'email' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'form'
      )
    ),
    'msg' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 45,
				'rows' => 10,
				'class' => 'form',
                                'id' => 'msg',
                                'onkeyup'=> 'checkLength(comment);',
                                'onchange'=> 'checkLength(comment);'
			)
		),
    'notify' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        		'value_force' => 'Y',
				'class' => ''
			)
		),
  ),

  // banner
  'adm_banner' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 20
      )
    ),
    'filename' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 40,
		'rows' => 10,
		'class' => 'form'
	)
    ),
    'link' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 100
      )
    ),
    'width' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 3
      )
    ),
    'height' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 3
      )
    ),
    'percent' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 3
      )
    ),
    'format' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 6
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
      )
    ),
    'foreign_ips' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
      )
    ),
    'order_no' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 3,
        'class' => 'input'
      )
    )
  ),

  
  // e-cards
  'cards' => array(
    'to_email' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'form'
      )
    ),
    'to_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 16,
        'class' => 'form'
      )
    ),
    'from_email' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'form'
      )
    ),
    'from_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'form'
      )
    ),
    'text' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 40,
				'rows' => 10,
				'class' => 'form'
			)
		),
  ),
  
  // e-cards2
  'cards2' => array(
    'to_email' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'to_name' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'from_email' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'from_name' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'text' => array(
      'type' => FORM_INPUT_HIDDEN
		),
  ),
  
  // feedback
  'feedback' => array(
    'name' => array(
      'type' => FORM_INPUT_TEXT,
			'params' => array(
				'class' => 'txtfld',
        'maxlength' => 128
			)
    ),
    'email' => array(
      'type' => FORM_INPUT_TEXT,
			'params' => array(
				'class' => 'txtfld',
        'maxlength' => 128
			)
    ),
    'subject' => array(
      'type' => FORM_INPUT_TEXT,
			'params' => array(
				'class' => 'txtfld',
        'maxlength' => 128
			)
    ),
    'body' => array(
      'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
			'class' => 'txtfld2',
        'cols' => 30,
        'rows' => 8
			)
    ),
  ),
  
  
  // community
  // community club
  // community categories admin
  'adm_com_cat' => array(
    'cat_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'allow_teams' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
	  	'size' => 1,
        'maxlength' => 1,
        'class' => 'input'
      )
    ),
    'order_no' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
	  	'size' => 4,
        'maxlength' => 4,
        'class' => 'input'
      )
    ),
  ),

  'forum_group' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
		'params' => array(
		'class' => 'input',
        'maxlength' => 32
			)
    ),
    'descr' => array(
      'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
			'class' => 'input',
        'cols' => 100,
        'rows' => 8
			)
    )
  ),
  
  'forum_group_event' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
  	'params' => array(
		'class' => 'input',
	        'maxlength' => 64,
		'size' => 64
	)
    ),
    'descr' => array(
      'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
			'class' => 'input',
        'cols' => 100,
        'rows' => 8
			)
    ),
    'results' => array(
      'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
			'class' => 'input',
        'cols' => 100,
        'rows' => 8
			)
    ),
    'finished' => array(
		'type' => FORM_INPUT_CHECKBOX,
		'params' => array(
	        'value_force' => 'Y',
			'class' => ''
		)
    ),  
  ),

  // community files
  'com_file' => array(
    'file_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'club_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128
      )
    ),
    'description' => array(
      'type' => FORM_INPUT_TEXTAREA,
      'params' => array(
        'class' => 'input',
        'cols' => 30,
        'rows' => 6
      )
    )
  ),

  'com_link' => array(
    'link_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'club_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128
      )
    ),
    'description' => array(
      'type' => FORM_INPUT_TEXTAREA,
      'params' => array(
        'class' => 'input',
        'cols' => 30,
        'rows' => 6
      )
    )
  ),

  // community messages
  'com_msg' => array(
    'message_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'club_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'thread_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'subject' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128
      )
    ),
    'body' => array(
      'type' => FORM_INPUT_TEXTAREA,
      'params' => array(
        'class' => 'input',
        'cols' => 30,
        'rows' => 6
      )
    )
  ),

  // community recommend
  'com_recommend' => array(
    'club_id' => array(
      'type' => FORM_INPUT_HIDDEN
    ),
    'email' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128
      )
    )
  ),
  
  
  // general admin
  'adm' => array(
    'where' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'input',
				'options' => array(
          '[E]' => 'Visi laukai',
          'TITLE' => 'Pavadinimas',
          'ANOT' => 'Anotacija',
          'DESCR' => 'Tekstas',
          'DATE_PUBLISHED' => 'Publikavimo data',
        )
			)
		),
    'query' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input'
      )
    ),
    'stat_from' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time()-((date('j')-1)*60*60*24),
        'class' => 'input'
      )
    ),
    'stat_to' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time(),
        'class' => 'input'
      )
    ),
  ),

  // pages admin
  'adm_researches' => array(
    'header' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 135,
		'rows' => 5,
		'class' => 'input_big'
      )
    )
  ),
  // pages admin
  'adm_pages' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'input',
        'size' => 140
      )
    ),  
    'page_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'input',
        'size' => 140
      )
    ),  
    'description' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 200,
				'rows' => 30,
				'class' => 'input_big'
			)
		),
    'descr_before' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 135,
				'rows' => 5,
				'class' => 'input_big'
			)
		),
    'descr_after' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 135,
				'rows' => 5,
				'class' => 'input_big'
			)
		),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
		        'value_force' => 'Y',
				'class' => ''
			)
		),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input',
        'size' => 30
      )
    ),
    'pic_title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input',
        'size' => 30
      )
    ),
    'date_from' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time()+60*60*24*30,
        'year_to' => date('Y')+1,
        'class' => 'input'
      )
    ),
    'date_to' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time()+60*60*24*30,
        'year_to' => date('Y')+1,
        'class' => 'input'
      )
    )
  ),
  // news admin
  'news' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'input',
        'size' => 60
      )
    ),
    'source_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 30,
        'class' => 'input',
        'size' => 30
      )
    ),
    'source' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'input',
        'size' => 60
      )
    ),
  ),

  'pm' => array(
    'subject' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input',
        'size' => 60
      )
    ),
    'receipient' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 12,
        'class' => 'input',
        'size' => 12
      )
    ),
  ),

  // news admin
  'video' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'input',
        'size' => 60
      )
    ),
    'source_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 30,
        'class' => 'input',
        'size' => 30
      )
    ),
    'source' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'input',
        'size' => 60
      )
    ),
    'thumbnail' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input',
        'size' => 60
      )
    ),
    'link' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
	        'class' => 'input',
		'cols' => 65,
		'rows' => 7
	)
    ),
  ),

  // news admin
  'adm_news' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'input_big',
        'size' => 110
      )
    ),
    'date_published' => array(
			// 'type' => FORM_INPUT_DATE,
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'year_to' => date('Y')+1,
        'class' => 'input'
      )
    ),
    'date_expired' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time()+60*60*24*150,
        'year_to' => date('Y')+1,
        'class' => 'input'
      )
    ),
    'group_id' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'input',
				'options' => array(
          NEWS_DOMESTIC => 'Lietuva',
          NEWS_WORLD => 'Užsienis'
        )
			)
		),
    'source' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input',
        'size' => 140
      )
    ),
    'source_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 30,
        'class' => 'input',
        'size' => 140
      )
    ),

    'source2' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input',
        'size' => 140
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 135,
				'rows' => 30,
				'class' => 'input_big',
                                'onclick'=> 'storeCaret(this);',
                                'onkeyup'=> 'storeCaret(this);',
                                'onselect'=> 'storeCaret(this);',
                                'onfocus'=> 'storeCaret(this);'
			)
		),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'blog_index' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'allow_publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input',
        'size' => 30
      )
    ),
    'pic2_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input',
        'size' => 30
      )
    ),
    'pic2_source' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input',
        'size' => 30
      )
    ),
    'link1' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'blog_responce' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'size' => 60,
        'class' => 'input'
      )
    ),
    'priority' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 1,
        'size' => 1,
        'class' => 'input'
      )
    ),
    'link1name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'link2' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'link2name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'link3' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'link3name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'keywords' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 40,
				'rows' => 3,
				'class' => 'input_big'
			)
		),
    'subject' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 255,
        'value' => 'krepsinis.net naujienos',
        'class' => 'input'
      )
    ),
  ),
  // news admin
  'blogs' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 512,
        'class' => 'txtfld2',
        'size' => 50
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 65,
				'rows' => 30,
			        'class' => 'txtfld2',
                                'onclick'=> 'storeCaret(this);',
                                'onkeyup'=> 'storeCaret(this);',
                                'onselect'=> 'storeCaret(this);',
                                'onfocus'=> 'storeCaret(this);'
			)
		),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
			        'value_force' => 'Y',
			        'class' => 'txtfld',
			)
		),
    'keywords' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 40,
				'rows' => 3,
				'class' => 'txtfld2'
			)
		),
  ),

  // task admin
  'adm_task' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 50,
        'class' => 'input_big',
        'size' => 50
      )
    ),
    'assigned' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 30,
        'class' => 'input_big',
        'size' => 20
      )
    ),
    'created_date' => array(
			// 'type' => FORM_INPUT_DATE,
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'year_to' => date('Y')+1,
        'class' => 'input'
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 135,
				'rows' => 30,
				'class' => 'input_big',
                                'onclick'=> 'storeCaret(this);',
                                'onkeyup'=> 'storeCaret(this);',
                                'onselect'=> 'storeCaret(this);',
                                'onfocus'=> 'storeCaret(this);'
			)
		),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
		)
	),
    'closed' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
		)
	),
  ),
  
  // source admin
  'adm_source' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 100,
        'class' => 'input'
      )
    ),
    'link' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 255,
        'class' => 'input'
      )
    ),
    'footer' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 40,
		'rows' => 10,
		'class' => 'form'
	)
    ),
    'has_footer' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
		'value_force' => 'Y',
 		'class' => ''       
	)
    ),
  ),

  // source admin
  'adm_stream' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 40,
        'class' => 'input'
      )
    ),
    'link' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'update_frequency' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'class' => 'input'
      )
    ),
  ),

  'adm_partners' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 100,
        'class' => 'input'
      )
    ),
    'text[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 60,
        'size' => 60,
        'class' => 'input'
      )
    ),
    'link[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 100,
        'size' => 100,
        'class' => 'input'
      )
    ),
    'descr' => array(
		'type' => FORM_INPUT_TEXTAREA,
		'params' => array(
			'cols' => 100,
			'rows' => 40,
			'class' => 'input'
		)
	),
  ),
  
  // help admin
  'adm_help' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'script' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 100,
				'rows' => 12,
				'class' => 'input'
			)
		),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => ''
			)
		),
  ),

  // online admin
  'adm_online' => array(
    'competition' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 100,
                'size' => 100,
		'class' => 'input'
	)
    ),
    'add_info' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 50,
                'size' => 50,
		'class' => 'input'
	)
    ),
    'header' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(		
                'size' => 50,
		'class' => 'input'
	)
    ),

    'team1' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 30,
                'size' => 30,
		'class' => 'input'
	)
    ),
    'team2' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 30,
                'size' => 30,
		'class' => 'input'
	)
    ),
    'score1' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 6,
                'size' => 6,
		'class' => 'input'
	)
    ),
    'score2' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 6,
                'size' => 6,
		'class' => 'input'
	)
    ),
    'descr' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 60,
		'rows' => 8,
		'class' => 'input_big'
		)
	),
    'new_comment' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 60,
		'rows' => 4,
		'class' => 'input_big'
		)
	),
  ),
  // online admin
  'adm_online_flash' => array(
    'competition' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 50,
                'size' => 50,
		'class' => 'input'
	)
    ),
    'add_info' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 20,
                'size' => 20,
		'class' => 'input'
	)
    ),
    'header' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(		
		'maxlength' => 20,
                'size' => 20,
		'class' => 'input'
	)
    ),

    'team1' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 20,
                'size' => 20,
		'class' => 'input'
	)
    ),
    'team2' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 20,
                'size' => 20,
		'class' => 'input'
	)
    ),
    'score1' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 3,
                'size' => 6,
		'class' => 'input'
	)
    ),
    'score2' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
		'maxlength' => 3,
                'size' => 6,
		'class' => 'input'
	)
    ),
    'descr' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 60,
		'rows' => 8,
		'class' => 'input_big'
		)
	),
    'new_comment' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 60,
		'rows' => 4,
		'class' => 'input_big'
		)
	),
    'kom' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),

  ),

  'adm_sms' => array(
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
                                'id' => 'descr', 
				'cols' => 80,
				'rows' => 8,
				'class' => 'form',
                                'onkeydown' => 'countChars();',
                                'onkeyup' => 'countChars();',
                                'onkeypress' => 'countChars();',
                                'onchange' => 'countChars();'
        )
      )
    ),

  // user admin
  'adm_clan' => array(
    'clan_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    )
  ),
  // user admin
  'adm_user' => array(
    'first_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'last_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'original_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'nickname' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 25,
        'class' => 'input'
      )
    ),
    'male' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'group_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'user_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 12,
        'class' => 'input'
      )
    ),
    'password' => array(
      'type' => FORM_INPUT_PASSWORD,
      'params' => array(
        'maxlength' => 16,
        'class' => 'input'
      )
    ),
    'password2' => array(
      'type' => FORM_INPUT_PASSWORD,
      'params' => array(
        'maxlength' => 16,
        'class' => 'input'
      )
    ),
    'password3' => array(
      'type' => FORM_INPUT_PASSWORD,
      'params' => array(
        'maxlength' => 16,
        'class' => 'input'
      )
    ),
    'email' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'local_email' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'phone' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'mobile_phone' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'country' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 50,
        'class' => 'input'
      )
    ),
    'town' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 50,
        'class' => 'input'
      )
    ),
    'address1' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'address2' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'postcode' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 20,
        'class' => 'input'
      )
    ),
    'citizenship' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'height' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'class' => 'input'
      )
    ),
    'weight' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'class' => 'input'
      )
    ),
    'gender' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'allow_blog' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'remember' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'birth_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => '9999-99-99',
        'year_to' => date('Y')-2,
        'class' => 'input'
      )
    ),
    'death_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => '9999-99-99',
        'year_to' => date('Y'),
        'class' => 'input'
      )
    ),

    'family_info' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 3,
				'class' => 'input'
			)
		),
    'ach_info' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 70,
				'rows' => 8,
				'class' => 'input'
			)
		),

    'profile' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 70,
				'rows' => 8,
				'class' => 'input'
			)
		),
    'add_info' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 70,
				'rows' => 8,
				'class' => 'input'
			)
		),
    'career_info' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 70,
				'rows' => 8,
				'class' => 'input'
			)
		),
    'hobby_info' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 70,
				'rows' => 8,
				'class' => 'input'
			)
		),
    'email_news' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'email_schedule' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'sms_news' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'sms_schedule' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'sms_results' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'activation_code' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 12,
        'class' => 'input'
      )
    ),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'publish' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'active' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'sms' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
          'value_force' => 'Y',
  	  'class' => ''
	)
     ),
    'admin' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'passive_admin' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'remember' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'points' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 10,
        'maxlength' => 10,
        'class' => 'input'
      )
    ),
    'cookiestring' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 34,
        'class' => 'input'
      )
    ),
    'date_box' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'res_box' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'sched_box' => array(
       'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'stand_box' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'stand_box_default' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'input',
				'options' => 'stand_box_default'
			)
		),
    'admin_default' => array(
       'type' => FORM_INPUT_TEXT,
       'params' => array(
          'size' => 20,
          'maxlength' => 64,
          'class' => 'input'
       )
     ),
    'credit' => array(
       'type' => FORM_INPUT_TEXT,
       'params' => array(
          'size' => 2,
          'maxlength' => 4,
          'class' => 'input'
       )
     ),

    'keywords' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 3,
				'class' => 'input'
			)
		),
    // user membership edit
    'user_type' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'input',
				'options' => array(
          '[E]' => '',
          USER_PLAYER => 'Žaidėjas',
          USER_TRAINER => 'Treneris',
          USER_ADMINISTRATION => 'Administracija'
        )
			)
		),
    'position_id1' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'input',
				'options' => 'positions'
			)
		),
    'position_id2' => array(
			'type' => FORM_INPUT_SELECT,
			'params' => array(
				'class' => 'input',
				'options' => 'positions'
			)
		),
    'num' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 4,
        'class' => 'input'
      )
    ),
    'date_started' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time(),
        'year_to' => date('Y')+1,
        'class' => 'input'
      )
    ),
    'date_expired' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => mktime(0, 0, 0, 8, 1, date("Y") + 1),
        'year_to' => date('Y')+2,
        'class' => 'input'
      )
    )
  ),

  // game admin
  'adm_tour' => array(
    'start_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()) - 1,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()) - 1,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'number' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 1,
        'class' => 'input'
      )
    ),
    'round' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 1,
        'class' => 'input'
      )
    )
  ),

  // game admin
  'adm_award' => array(
    'date_awarded' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => mktime(0, 0, 0, 6, 1, date("Y")),
        'year_to' => date('Y')+0,
        'class' => 'input'
      )
    ),

    'comment' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'size' => 50,
        'class' => 'input'
      )
    )
  ),
  
  // game admin
  'adm_game' => array(
    'start_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()) - 100,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()),
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 11,
				'class' => 'input'
			)
		),
    'note' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 25,
        'class' => 'input'
      )
    ),
    'location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input'
      )
    ),
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'subgroup' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 1,
        'class' => 'input'
      )
    ),
    'score1' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'class' => 'input'
      )
    ),
    'score2' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'class' => 'input'
      )
    ),
    'sms' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
	)
    ),
    'online' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
	)
    ),
    'keywords' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 3,
				'class' => 'input'
			)
		),
    'translation_date' => array(
			'type' => FORM_INPUT_DATETIME,
			'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()),
        'year_to' => date('Y', time()) + 1,
				'class' => 'input'
      )
		),
    'subject' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 255,
        'value' => 'krepsinis.net tvarkarastis',
        'class' => 'input'
      )
    ),
    // individual stats !!!!!!!!!!!!!
    'team_id[]' => array(
      'type' => FORM_INPUT_HIDDEN,
    ),
    'user_id[]' => array(
      'type' => FORM_INPUT_HIDDEN,
    ),
    'score[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'played[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'pt2_scored[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'pt2_thrown[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'pt3_scored[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'pt3_thrown[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'pt1_scored[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'pt1_thrown[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'rebounds[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'assists[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'steals[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'blocks[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'mistakes[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'fauls[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 1,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'unfauls[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'koeff[]' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 2,
        'class' => 'input'
      )
    ),
  ),
  
  // tournament admin
  'adm_league' => array(
    'tname' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 10,
				'class' => 'input'
			)
		),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'pic2_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => ''
			)
		),
    'keywords' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 3,
				'class' => 'input'
			)
		),
  ),
  
  // tournament seasonadmin
  'adm_season' => array(
    'season_title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
	'size' => 40,
        'class' => 'input'
      )
    ),
    'start_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => '9999-99-99',
        'year_from' => date('Y', time()) - 100,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => '9999-99-99',
        'year_from' => date('Y', time()) - 100,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'standings' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
          'value_force' => 'Y',
  	  'class' => ''
	)
    ),
    'static_standings' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
          'value_force' => 'Y',
  	  'class' => ''
	)
    ),
    'topstats' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
          'value_force' => 'Y',
  	  'class' => ''
	)
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'subgroup' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'size' => 3,
        'class' => 'input'
      )
    ),
    'final' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),

  ),

  // tournament seasonadmin
  'adm_jobs' => array(
    'job_title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 20,
	'size' => 40,
        'class' => 'input'
      )
    ),
    'job_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => '9999-99-99',
        'year_from' => date('Y', time()) - 1,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'job_text' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 60,
		'rows' => 9,
		'class' => 'input'
		)
	),

  ),

  'adm_tseason' => array(
    'tseason_title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'start_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => '9999-99-99',
        'year_from' => date('Y', time()) - 2,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => '9999-99-99',
        'year_from' => date('Y', time()) - 2,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
		)
	),
    'wap_separate' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
		)
	),
    'wap_only' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
		)
	),
    'monthly' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
			'class' => ''
			)
		),
    'weekly' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
			'class' => ''
			)
		),
    'rules' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 60,
				'rows' => 9,
				'class' => 'input'
			)
		),
    'wap_rules' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 60,
				'rows' => 9,
				'class' => 'input'
			)
		),

    'prizes' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 60,
				'rows' => 9,
				'class' => 'input'
			)
		),
    'wap_prizes' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 60,
				'rows' => 9,
				'class' => 'input'
			)
		),

    'duk' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 60,
				'rows' => 9,
				'class' => 'input'
			)
		),
    'wap_duk' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 60,
				'rows' => 9,
				'class' => 'input'
			)
		),


  ),
  
  // team admin
  'adm_team' => array(
    'team_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'team_name2' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'original_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'city' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'country' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'gender' => array(
      'type' => FORM_INPUT_CHECKBOX,
      'params' => array(
        'value_force' => 'Y',
        'class' => ''
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
		)
	),
    'newsletter' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
		)
	),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input',
        'size' => 30
      )
    ),
  ),
  
  // organization admin
  'adm_org' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'address' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 500,
        'class' => 'input'
      )
    ),
    'link' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 10,
				'class' => 'input'
			)
		),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'pic2_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => ''
			)
		),
    'keywords' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 3,
				'class' => 'input'
			)
		),
  ),
  
  // organization types admin
  'adm_orgtype' => array(
    'orgtype_title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
  ),
  
  // shortcut admin
  'adm_shortcut' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 100,
        'class' => 'input'
      )
    ),
    'link' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'order_no' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 4,
        'size' => 3,
        'class' => 'input'
      )
    ),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => ''
			)
		),
  ),
  
  // categories admin
  'adm_cat' => array(
    'cat_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
  ),

  // categories admin
  'adm_item_type' => array(
    'item_type_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'default_value' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),

  ),

  'adm_item' => array(
    'item_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'size' => 30,
        'class' => 'input'
      )
    ),
    'price' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'price_euro' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'price_credits' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'level' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'price_sell' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'action_value' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'prevent_injury' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 1,
	'size' => 1,
        'class' => 'input'
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input',
        'size' => 30
      )
    ),

  ),

  'adm_skill' => array(
    'attr_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'price' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'levels' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'value' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
  ),

  // categories admin
  'adm_country' => array(
    'latin_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'original' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
	'size' => 32,
        'class' => 'input'
      )
    ),
    'country_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
	'size' => 32,
        'class' => 'input'
      )
    ),
    'short_code' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
	'size' => 3,
        'class' => 'input'
      )
    ),
    'cctld' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
	'size' => 2,
        'class' => 'input'
      )
    )
  ),

  // categories admin
  'adm_forum' => array(
    'forum_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 100,
        'class' => 'input'
      )
    ),
    'topic_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
	'size' => 64,
        'class' => 'input'
      )
    ),
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
	'size' => 64,
        'class' => 'input'
      )
    ),
    'text' => array(
      'type' => FORM_INPUT_TEXTAREA
    ),

    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => ''
			)
		),
  ),
  
  
  // keywords admin
  'adm_keyword' => array(
    'keyword' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
  ),
  
  // tv admin
  'adm_tv' => array(
    'tv_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 32,
        'class' => 'input'
      )
    ),
    'logo' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'link' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 200,
        'class' => 'input'
      )
    ),
  ),
  
  // totalizator admin
  'adm_tot' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 50,
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
   'weight' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'size' => 2,
        'class' => 'input'
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 40,
				'rows' => 9,
				'class' => 'input'
			)
		),
    'descr_wap' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 40,
				'rows' => 9,
				'class' => 'input'
			)
		),
    'start_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'year_from' => date('Y', time()) - 1,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => time()+60*60*24*30,
        'year_from' => date('Y', time()) - 1,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => 'input'
			)
		),
    'won[]' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
				'class' => ''
			)
		),
    'include[]' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
				'class' => ''
			)
		),

  ),
   
  // contest admin
  'adm_contest' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 40,
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'question' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 40,
        'maxlength' => 200,
        'class' => 'input'
      )
    ),
    'answer' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 40,
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'value' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 4,
        'maxlength' => 3,
        'class' => 'input'
      )
    ),
    'question_per_attempt' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 3,
        'maxlength' => 2,
        'class' => 'input'
      )
    ),
    'max_points' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 3,
        'maxlength' => 2,
        'class' => 'input'
      )
    ),
    'start_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()) - 100,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()),
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'wap' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'use_credits' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'descr' => array(
		'type' => FORM_INPUT_TEXTAREA,
		'params' => array(
			'cols' => 60,
			'rows' => 20,
			'class' => 'input'
		)
	),
    'descr_index' => array(
		'type' => FORM_INPUT_TEXTAREA,
		'params' => array(
			'cols' => 60,
			'rows' => 5,
			'class' => 'input'
		)
	),

  ),
  // contest admin
  'adm_conference' => array(
    'conference_title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 40,
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'user_name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 12,
        'maxlength' => 12,
        'class' => 'input'
      )
    ),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
	'class' => ''
	)
    ),
    'question' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 50,
		'rows' => 15,
	        'class' => 'input'
	)
    ),
    'answer' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 50,
		'rows' => 15,
	        'class' => 'input'
	)
    )

  ),
  
  // card admin
  'adm_card' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input'
      )
    ),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => ''
			)
		),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'pic2_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
  ),

  'adm_portal_event' => array(
    'event_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'year_from' => date('Y', time()) - 1,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
  ),  

  // events admin
  'adm_event' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'event_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'year_from' => 1890,
        'class' => 'input'
      )
    ),
    'descr' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 50,
				'rows' => 10,
				'class' => 'input'
			)
		),
    'publish' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
        'value_force' => 'Y',
				'class' => ''
			)
		),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'pic2_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'keywords' => array(
			'type' => FORM_INPUT_TEXTAREA,
			'params' => array(
				'cols' => 40,
				'rows' => 3,
				'class' => 'input'
			)
		),
  ),

  // forum banned ip admin
  'adm_miniclip' => array(
    'script' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
	'cols' => 50,
	'rows' => 10,
	'class' => 'input'
       ) 
    ),
    'pic_location' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 128,
        'class' => 'input',
        'size' => 30
      )
    ),
    'external' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
  ),

  'adm_gallery' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128,
	'size' => 64
      )
    ),
    'filename' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input',
        'size' => 30
      )
    ),
    'thumbnail' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 256,
        'class' => 'input',
        'size' => 100
      )
    ),
    'link' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 40,
		'rows' => 10,
		'class' => 'form'
	)
    ),
    'include[]' => array(
			'type' => FORM_INPUT_CHECKBOX,
			'params' => array(
				'class' => '',
				'value_force' => 'Y'
			)
		)

  ),
  'adm_newsletter' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128,
	'size' => 64
      )
    ),
    'name' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128,
	'size' => 128
      )
    ),
    'descr' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 100,
		'rows' => 10,
		'class' => 'input'
	)
    ),
    'footer' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 100,
		'rows' => 10,
		'class' => 'input'
	)
    ),
    'header' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 100,
		'rows' => 10,
		'class' => 'input'
	)
    ),

    'frequency' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 3,
	'size' => 3
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'year_to' => date('Y')+1,
        'class' => 'input'
      )
    ),

    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
	)
     ),

  ),
  // banner
  'adm_gallery_pic' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'class' => 'input',
        'maxlength' => 128,
	'size' => 64
      )
    ),
    'filename' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
           'class' => 'input',
           'maxlength' => 64,
     	   'size' => 20
	)
    )
  ),

 'adm_manager' => array(
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'start_value' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 6,
        'class' => 'input'
      )
    ),
    'money' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 6,
        'class' => 'input'
      )
    ),
    'money_stock' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 6,
        'class' => 'input'
      )
    ),
    'transactions' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'class' => 'input'
      )
    ),
    'max_players' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'class' => 'input'
      )
    ),
    'prize_fund' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
        'class' => 'input'
      )
    ),
    'donated' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 5,
        'class' => 'input'
      )
    ),
    'allow_view' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
      )
    ),
    'ignore_leagues' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
      )
    ),
    'reminder' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
        'value_force' => 'Y',
		'class' => ''
      )
    ),
    'fee' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 3,
        'class' => 'input',
        'size' => 3
      )
    ),
    'start_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()) - 100,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()) - 100,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'registration_end_date' => array(
      'type' => FORM_INPUT_DATETIME,
      'params' => array(
        'value' => '9999-99-99 99:99',
        'year_from' => date('Y', time()) - 1,
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'captaincy' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'allow_substitutes' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'allow_stock' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'allow_rvs_leagues' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'allow_clan_teams' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'allow_solo' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'rvs_leagues_last_tour' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 2,
        'class' => 'input',
        'size' => 2
      )
    ),

  ),

  'league' => array(
     'recruitment_active' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
     'accept_newbies' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
     'real_prizes' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	)


  ),

  // tournament admin
  'adm_manager_league' => array(
    'rules' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 50,
		'rows' => 10,
		'class' => 'input'
		)
	)
  ),
  // categories admin
  'adm_hint' => array(
    'hint_title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 64,
        'class' => 'input'
      )
    ),
    'descr' => array(
      'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'class' => 'input',
	        'cols' => 100,
	       	'rows' => 8
	)
    ),
  ),

 'adm_survey' => array(
  // poll admin
    'title' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 30,
        'maxlength' => 64,
        'class' => 'form'
      )
    ),

    'question' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 40,
        'maxlength' => 512,
        'class' => 'input'
      )
    ),
    'start_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time(),
        'year_from' => date('Y', time()),
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'end_date' => array(
      'type' => FORM_INPUT_DATE,
      'params' => array(
        'value' => time()+60*60*24*30,
        'year_from' => date('Y', time()),
        'year_to' => date('Y', time()) + 1,
        'class' => 'input'
      )
    ),
    'priority' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'maxlength' => 1,
        'size' => 1,
        'class' => 'input'
      )
    ),
    'answers' => array(
	'type' => FORM_INPUT_TEXTAREA,
	'params' => array(
		'cols' => 70,
		'rows' => 5,
		'class' => 'input'
	)
    ),
    'answer' => array(
      'type' => FORM_INPUT_TEXT,
      'params' => array(
        'size' => 40,
        'maxlength' => 128,
        'class' => 'input'
      )
    ),
    'allow_change' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	),
    'publish' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => ''
		)
	)
     ),
  
 'user_settings' => array(
    'pm_email' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => 'input'
		)
	),
    'stock_profit_email' => array(
	'type' => FORM_INPUT_CHECKBOX,
	'params' => array(
	        'value_force' => 'Y',
		'class' => 'input'
		)
	)

 ),

 'manager_report'=> array(
    'link' => array(
	'type' => FORM_INPUT_TEXT,
	'params' => array(
	        'size' => 40,
	        'maxlength' => 256,
		'class' => 'input'
		)
	),
  )

);

?>