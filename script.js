
function bascule(elem)
{
   etat=document.getElementById(elem).style.display;
   if(etat=="none"){
	 document.getElementById(elem).style.display="block";
   }
   else{
	 document.getElementById(elem).style.display="none";
   }
}

function CocheColonneSelect($max) {
	for (var i=1;i<=$max;i++) {
		if(document.getElementById('classe_'+i)){
			document.getElementById('classe_'+i).checked = true;
		}
	}
}


function DecocheColonneSelect($max) {
	for (var k=1;k<=$max;k++) {
		if(document.getElementById('classe_'+k)){
			document.getElementById('classe_'+k).checked = false;
		}
	}
}