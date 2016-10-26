function Drawer (elm,shortLabel) {
  this.element=elm;
  this.full=elm.innerHTML;
  this.short=shortLabel;
  this.isOpen=1;
  
  var tthis=this;
  var onclickToggle='toggle('+"'"+this.short+"'"+');return false;';
  
  this.close=function(){
    this.element.innerHTML='<a href="javascript:void(0)" onclick="'+onclickToggle+'">'+this.short+"&raquo;"+"</a>";
    this.isOpen=0;
  }
  
  this.open=function(){
    var closeLink='<a href="javascript:void(0)" onclick="'+onclickToggle+'">'+"&laquo;"+"</a>";
    this.element.innerHTML=this.full+closeLink;
    this.isOpen=1;
  }
  
}

//var ahrefs=document.getElementsByTagName("a");
var forms=document.forms;
var d=new Array;
var f;
var nd;

for (var i=0;i<forms.length;i++) {
  f=forms[i];
  if ( f.hasAttribute("short") ) {
    //alert(f.getAttribute("short"));
    nd=new Drawer(f.parentNode,f.getAttribute("short"));
    d.push(nd);
  }
}
for (var i=0;i<d.length;i++) {
  d[i].close();  
}

function toggle(shortLabel) {
  //alert("fNc");
  for (var i=0;i<d.length;i++) {
    if ( d[i].short === shortLabel ) {
      if ( d[i].isOpen ) d[i].close();
      else d[i].open();
      break;
    }
  }
}
