/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
    config.toolbar = 'MyToolbar';
    config.toolbar_MyToolbar =
    [
        ['Cut','Copy','Paste'],
        ['Undo','Redo','-','Find','Replace','-','SelectAll', 'RemoveFormat'],
        ['Image','Table','HorizontalRule','Smiley'], ['Bold','Italic','Strike'], ['Link','Unlink','Anchor'], 
        '/',
        ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
	['Styles','Format','Font','FontSize'],
        '/',
	['TextColor','BGColor']
    ];
};


CKEDITOR.config.smiley_columns=14;
CKEDITOR.config.smiley_path=CKEDITOR.basePath+'plugins/smiley/images/tsc/';
CKEDITOR.config.smiley_images=['001_cool.gif','001_huh.gif','001_rolleyes.gif','001_smile.gif','001_tongue.gif','001_tt1.gif','001_tt2.gif','001_unsure.gif','001_wub.gif','angry.gif','biggrin.gif','bl_ink.gif','blush.gif','blushing.gif','bored.gif','closedeyes.gif','confused1.gif','cool.gif','crying.gif','cursing.gif','drool.gif','glare.gif','huh.gif','laugh.gif','lol.gif','mad.gif','mellow.gif','ohmy.gif','sad.gif','scared.gif','sleep.gif','sneaky2.gif','thumbdown.gif','thumbup.gif','thumbup1.gif','tongue_smilie.gif','w00t.gif','wink.gif','001_9898.gif','001_icon16.gif','1eye.gif','alien.gif','alucard.gif','angel.gif','arabia.gif','asshole.gif','balloon.gif','scooter.gif','batman.gif','beta1.gif','boat.gif','censored.gif','chef.gif','chinese.gif','chris.gif','clap.gif','clover.gif','happybday.gif','cool2.gif','cowboy.gif','death.gif','detective.gif','devil.gif','devil2.gif','donatello.gif','tooth.gif','double fuck.gif','eek.gif','euro.gif','excl.gif','flowers.gif','gun_bandana.gif','fuk2.jpg','gunsmilie.gif','fuck-8.gif','hammer.gif','taz.gif','clown.gif','helpsmilie.gif','innocent.gif','kiss.gif','ninja.gif','no.gif','nono.gif','nuke.gif','offtopic.gif','online2long.gif','oops.gif','osama.gif','ph34r.gif','phone.gif','pinch.gif','punk.gif','red_indian.gif','rockon.gif','rolleyes.gif','saddam.gif','sailor.gif','santa.gif','ban.gif','shaun.gif','shifty.gif','shit.gif','shuriken.gif','single fuck.gif','sleep1.gif','sleeping.gif','smartass.gif','sorcerer.gif','stuart.gif','tt1.gif','surrender.gif','sweatdrop.gif','tank.gif','hang.gif','dots.gif','stupid.gif','tt2.gif','turned.gif','wacko.gif','walkman.gif','wheelchair.gif','whistling.gif','winkiss.gif','wub.gif','yawn.gif','yes.gif','chess.gif', 'yinyang.gif', 'beer.gif', 'buddies.gif', 'biggrin2.gif'];
CKEDITOR.config.smiley_descriptions=['', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '','', '', '', '', '', '', '', ''];