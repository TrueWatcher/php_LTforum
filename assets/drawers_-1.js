function Drawer (elm,shortLabel) {
  var _full=elm.innerHTML;
  var _isOpen=1;
  var onclickToggle='toggle('+"'"+shortLabel+"'"+');return false;';

  this.close=function(){
    elm.innerHTML='<a href="javascript:void(0)" onclick="'+onclickToggle+'">'+shortLabel+"&raquo;"+"</a>";
    _isOpen=0;
  };

  this.open=function(){
    var closeLink='<a href="javascript:void(0)" onclick="'+onclickToggle+'">'+"&laquo;"+"</a>";
    elm.innerHTML=_full+"&nbsp;&nbsp;"+closeLink;
    _isOpen=1;
  };

  this.flip=function(){
    if (_isOpen) this.close();
    else this.open();
  };

  this.check=function(label){
    if ( shortLabel === label ) return (true);
    else return (false);
  };
}// end Drawer

function makeDrawers(collection) {
  var i,f,sh,nd;
  for (i=0;i<collection.length;i++) {
    f=collection[i];
    sh = ( f["short"] || f.short || f.getAttribute("short") || f.attributes["short"] );
    if ( sh ) { // find the parent and turn it into a Drawer
      nd=new Drawer(f.parentNode,sh);
      d.push(nd);
    }
  }
}// end makeDrawers(collection)

var d=[];// global

makeDrawers(document.forms);
makeDrawers(document.links);

for (var i=0;i<d.length;i++) {
  d[i].close();
}

function toggle(shortLabel) {
  for (var i=0;i<d.length;i++) {
    if ( d[i].check(shortLabel) ) {
      d[i].flip();
      return;
    }
  }
}// end toggle

