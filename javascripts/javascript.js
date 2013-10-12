var editor;
function replaceEditor(control)
{
    if ( editor )
      editor.destroy();
    
    editor = CKEDITOR.replace( control ,
            {   
		toolbar : 'MyToolbar',
		resize_enabled: false,
		entities : true,
                  on :
                 {
	           instanceReady : function( ev )
	            {
                     // Output paragraphs as <p>Text</p>.
	                this.dataProcessor.writer.setRules( 'p',
	                    {
	                        indent : false,
                                breakBeforeOpen : false,
	                        breakAfterOpen : false,
	                        breakBeforeClose : false,
	                        breakAfterClose : false
                             });
	            }
	        }
	});
}


function toggle(divid, vThis) {
  var mydiv = document.getElementById(divid);
  if(mydiv.style.display=='none') {
     mydiv.style.display = 'block';
     vThis.src="./img/icons/minus.png";
  }
  else if(mydiv.style.display!='none') {
     mydiv.style.display = 'none';
     vThis.src="./img/icons/plus.png";
  }
};

function toggle2(divid, vThis) {
  var mydiv = document.getElementById(divid);
  if(mydiv.style.display=='none') {
     mydiv.style.display = 'block';
  }
  else if(mydiv.style.display!='none') {
     mydiv.style.display = 'none';
  }
};

function toggleSmall(divid, imageid) {
  var mydiv = document.getElementById(divid);
  var myimg = document.getElementById(imageid);
  if(mydiv.style.display=='none') {
     mydiv.style.display = 'block';
     myimg.src="./img/icons/small_minus.png";
     createCookie(divid,'expand',365);
  }
  else if(mydiv.style.display!='none') {
     mydiv.style.display = 'none';
     myimg.src="./img/icons/small_plus.png";
     createCookie(divid,'collapse',365);
  }
};

function quote(edit_id, source_id, author) {
  var myedit = document.getElementById(edit_id);
  var text = document.getElementById(source_id).innerText;
  if (typeof(text) == "undefined")
    text = document.getElementById(source_id).textContent;
  var instance = CKEDITOR.instances[edit_id];
  text = "<p></p><div style=\"background-color:#8FBEF2;color:#000\" class=\"quotetitle\">" +author+"</div><div style=\"background-color:#E7FFE7\" class=\"quotecontent\">" + text+ "</div><p></p>";
  instance.setData(instance.getData() + text);

}

function reply(edit_id, source_id, author, subject_id) {
  var myedit = document.getElementById(edit_id);
  var text = document.getElementById(source_id).innerText;
  if (typeof(text) == "undefined")
    text = document.getElementById(source_id).textContent;
  var instance = CKEDITOR.instances[edit_id];
  text = "<p></p><div style=\"background-color:#8FBEF2;color:#000\" class=\"quotetitle\">" +author+"</div><div style=\"background-color:#E7FFE7\" class=\"quotecontent\">" + text+ "</div><p></p>";
  instance.setData(instance.getData() + text);

  var subject_text = document.getElementById(subject_id).innerText;
  if (typeof(subject_text) == "undefined")
    subject_text = document.getElementById(subject_id).textContent;
  subject_text = "Re: " + subject_text;
  var subject = document.getElementById('subject');

  subject.value=subject_text;

  /*var receipient = document.getElementById('receipient');
  receipient.value=author;*/
  addUsersReceipients(-1, author, "receipients");
}

function toggleControl(div_id1, div_id2) {
  var mydiv = document.getElementById(div_id1);
  var mydiv2 = document.getElementById(div_id2);
  mydiv.style.display = 'none';
  mydiv2.style.display = 'block';
}

/*==================================================
  Cookie functions
  ==================================================*/
function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

window.onload = function() {
    var ids = document.getElementsByTagName("div");
    for(var i=0;i<ids.length;i++) {
       try {
           if(ids[i].className == "collapsable") {
              if( readCookie(ids[i].id) == "collapse" ) {
                   toggleSmall(ids[i].id, "toggle_image_" + ids[i].id);
              }
           }
       } catch(e) {}
    }
}

var mCal;
function attachCalendar(obj) {
    var dhxCalendarData = {
        parent: obj,
        isAutoDraw: true};
    mCal = new dhtmlxCalendarObject(dhxCalendarData);
}