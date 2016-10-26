function Drawer (elm,shortLabel) {
  this.element=elm;
  this.full=elm.innerHTML;
  this.short=shortLabel;
  this.isOpen=1;
  
  //var tthis=this;
  var onclickToggle='toggle('+"'"+this.short+"'"+');return false;';
  
  this.close=function(){
    this.element.innerHTML='<a href="javascript:void(0)" onclick="'+onclickToggle+'">'+this.short+"&raquo;"+"</a>";
    this.isOpen=0;
  }
  
  this.open=function(){
    var closeLink='<a href="javascript:void(0)" onclick="'+onclickToggle+'">'+"&laquo;"+"</a>";
    this.element.innerHTML=this.full+"&nbsp;&nbsp;"+closeLink;
    this.isOpen=1;
  }
  
  this.switch=function(){
    if (this.isOpen) this.close();
    else this.open();
  }
  
  this.check=function(label){
    if ( this.short === label ) return (true);
    else return (false);    
  }
  
}// end Drawer

function makeDrawers(collection) {
  var f;
  for (var i=0;i<collection.length;i++) {
    f=collection[i];
    if ( f.hasAttribute("short") ) {// find the parent and turn it into a Drawer
      //alert(f.getAttribute("short"));
      nd=new Drawer(f.parentNode,f.getAttribute("short"));
      d.push(nd);
    }
  }
}// end makeDrawers(collection)

var d=new Array;

makeDrawers(document.forms);
makeDrawers(document.links);

for (var i=0;i<d.length;i++) {
  d[i].close();  
}

function toggle(shortLabel) {
  for (var i=0;i<d.length;i++) {
    if ( d[i].check(shortLabel) ) {
      d[i].switch();
      return;
    }
  }
}// end toggle

