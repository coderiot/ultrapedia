/**
 * From http://www.itnewb.com/v/Cross-Browser-CSS-Opacity-and-the-JavaScript-Fade-Fading-Effect/page2
 */

function getElm(eID) {
	return document.getElementById(eID);
}
function show(eID) {
	getElm(eID).style.display='block';
}
function hide(eID) {
	getElm(eID).style.display='none';
}
function setOpacity(eID, opacityLevel) {
	var eStyle = getElm(eID).style;
	eStyle.opacity = opacityLevel / 100;
	eStyle.filter = 'alpha(opacity='+opacityLevel+')';
}
function fade(eID, startOpacity, stopOpacity, duration) {
	var speed = Math.round(duration / 100);
	var timer = 0;
	if (startOpacity < stopOpacity){
		for (var i=startOpacity; i<=stopOpacity; i++) {
			setTimeout("setOpacity('"+eID+"',"+i+")", timer * speed);
			timer++;
		} return;
	}
	for (var i=startOpacity; i>=stopOpacity; i--) {
		setTimeout("setOpacity('"+eID+"',"+i+")", timer * speed);
		timer++;
	}
}
function fadeIn(eID) {
	setOpacity(eID, 0); show(eID); var timer = 0;
	for (var i=1; i<=100; i++) {
		setTimeout("setOpacity('"+eID+"',"+i+")", timer * 5);
		timer++;
	}
}
function fadeOut(eID, multiplier) {
	var timer = 0;
	for (var i=100; i>=1; i--) {
		setTimeout("setOpacity('"+eID+"',"+i+")", timer * multiplier);
		timer++;
	}
	setTimeout("hide('"+eID+"')", (timer + 1) * multiplier);
}