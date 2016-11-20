"use strict";
function Drawer(control,shortLabel) {
  var _this=this;
  var _isOpen=0;
  var _labelCover=shortLabel+"&raquo;";
  var _labelClose="&laquo;";
  var _container=control.parentNode;
  var _handle=document.createElement("a");
  var _handleStyle=_handle.style;
  var _controlStyle=control.style;

  _handle.href="javascript:;";
  _handle.innerHTML=_labelCover;
  _handle.onclick=function() { _this.flip(); return false; };
  _handleStyle.margin="0 0 0 1em";
  _handleStyle.padding="0 0.2em 0 0.2em";
  _controlStyle.display="none";
  _container.appendChild(_handle);

  this.flip=function() {
    if (_isOpen) {
      _controlStyle.display="none";
      _handle.innerHTML=_labelCover;
      _isOpen=0;
    }
    else {
      _handle.innerHTML=_labelClose;
      _controlStyle.display="";
      _isOpen=1;
    }
  };
}// end Drawer

function makeDrawers(collection) {
  var i,f,sh;
  for ( i=collection.length-1; i>=0; i-- ) {
    f=collection[i];
    sh = ( f["drawer"] || f.getAttribute("drawer") || f.attributes["drawer"] );
    if (sh) { new Drawer(f,sh); }
  }
}

var drawers=[];
if ( typeof(document.querySelectorAll)=="function" ) {
  drawers=document.querySelectorAll("[drawer]");// attribute selector
}
if ( drawers.length ) { makeDrawers(drawers); }
else {
  //alert ("dumb one");
  makeDrawers(document.forms);
  makeDrawers(document.links);
  makeDrawers(document.getElementsByTagName("span"));
}