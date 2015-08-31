/**
 * Ultrapedia-API
 * Christian Becker <http://beckr.org#chris>
 */
 
/**
 * The global object to be initialized from inline code
 */
var ultrapediaAPI;

function ultrapediaAPI_highlight(line) {
	var self = this;
	this.line = line;
	
	/* Install hooks */
	
	addOnloadHook(function() { self.highlightLine() });
	
	if (typeof window.addEventListener != "undefined"){
		window.addEventListener("resize", ultrapediaAPI_highlight_highlightTimer, false);
	}
	else if (typeof window.attachEvent != "undefined") {
		window.attachEvent("onresize", ultrapediaAPI_highlight_highlightTimer);
	}
}

ultrapediaAPI_highlight.prototype = {
	/**
	 * The line to be highlighted - set from in-line script
	 */
	line: undefined,
	
	/**
	 * Scroll offset when the highlight was first created; used to calculate new offset when textarea is scrolled
	 */
	savedScrollTop: undefined,
	
	/**
	 * Full height of highlight
	 */
	highlightHeight: undefined,

	highlightTimeout: undefined,

	didInitialJump: false,

	busy: false,

	debug: false,

	textBox: undefined,
	
	highlight: undefined,
	
	scrollPre: undefined,
	
	textLines: undefined,
	
	scrolledText: undefined,
	
	posBefore: undefined
};

ultrapediaAPI_highlight.prototype.highlightLine = function(step) {
	if (step == undefined)
		step = 0;
		
	switch (step) {
		case 0:
	
		if (this.busy)
			return;

		this.clearHighlightTimer();
			
		if (this.debug) {
			var oldScrollPre = document.getElementById('scrollPre');
			if (oldScrollPre) {
				oldScrollPre.parentNode.removeChild(oldScrollPre);
			}
		}
			
		this.busy = true;
			
		/* Inspired by http://wiki.sheep.art.pl/Textarea%20Scrolling */
		this.textBox = document.getElementById('wpTextbox1');
		this.highlight = document.getElementById('ultrapedia-highlight');
		this.textLines = this.textBox.value.replace(/\r/g,'').match(/(.*\n)/g);
		
		if (this.textLines == null)
			return; /* empty */
			
		this.scrolledText = '\n\n\n'; /* three lines to force scrollbars on ff2/windows */
		
		for (var i = 0; i < this.textLines.length && i < this.line-1; ++i) {
			this.scrolledText += this.textLines[i];
		}
		
		/* Calculate lengths */
		this.scrollPre = document.createElement('textarea');
		this.scrollPre.id = 'scrollPre';
		this.textBox.parentNode.appendChild(this.scrollPre);
			
		this.scrollPre.style.height = "48px";
		
		/*
		 * IE6 fix: scrollPre sometimes expands based on the content, 
		 * so provide absolute width taken from textBox.
		 */
		if (navigator.appVersion.indexOf("MSIE 6.") !=-1) {
			this.scrollPre.style.width = this.textBox.offsetWidth + "px";
		}
			
		/* If vertical scrollbars are shown... */
		if (this.textBox.offsetWidth - this.textBox.clientWidth > 8) {
			/* .. set scrollbars (always applies to Internet Explorer) */
			try { this.scrollPre.style.overflowY = "scroll"; } catch(e) {};
		} else {
			try { this.scrollPre.style.overflowY = "hidden"; } catch(e) {};
		}
		
		/* Get length before line */
		this.scrollPre.value = this.scrolledText.replace(/\n$/, '\n ');
		window.setTimeout("ultrapediaAPI.highlightLine(1);", 50);
		break;

	case 1:			
		this.posBefore = this.scrollPre.scrollHeight - 64;
		
		/* Need to handle first line specifically, as the line height will be one line even without content */
		if (this.line == 1)
			this.posBefore = 0;
				
		/* Get length including line to determine the number of lines that our line actually spans */
		this.scrollPre.value = this.scrolledText + this.textLines[this.line-1].replace(/\n$/, '\n ');
		window.setTimeout("ultrapediaAPI.highlightLine(2);", 50);
		break;
		
	case 2:
			
		var posAfter = this.scrollPre.scrollHeight - 64;
				
		var lineHeight = Math.max(posAfter - this.posBefore, 0);
				
		if (!this.debug)
			this.scrollPre.parentNode.removeChild(this.scrollPre);
		
		if (!this.didInitialJump) {
			if (this.textBox.setSelectionRange) {
				// Non-IE
				// Move the cursor
				this.textBox.setSelectionRange(this.scrolledText.length - 3, this.scrolledText.length - 3);
			} else if (this.textBox.createTextRange) {
				// Internet Explorer
				// We don't need to scroll, it will do it automatically, just move
				// the cursor.		
				var range = this.textBox.createTextRange();
				range.collapse(true);
				range.moveEnd('character', this.scrolledText.length - 3);
				range.moveStart('character', this.scrolledText.length - 3);
				range.select();
			}	
			
			/*
			 * Jump to position for all browsers.
			 * IE already scrolls to make the cursor visible at the bottom, but we want it at the top!
			 */
			this.textBox.focus();
			this.textBox.scrollTop = this.posBefore;
			this.didInitialJump = true;
		}
		
		this.savedScrollTop = this.posBefore;
	
		/* Highlight the whole line */
		this.highlightHeight = lineHeight;
		this.updateHighlight();
		
		/* Add listeners to remove the highlight if contents change */
		var self = this;
		this.textBox.onscroll = function() { self.scrollHandler(); }; /* note: doesn't work with FF2, see https://bugzilla.mozilla.org/show_bug.cgi?id=35011#c105 */
		this.textBox.onkeyup = function(evt) {
			evt = evt || window.event;
			if (!(evt && ((evt.keyCode >= 16 && evt.keyCode <= 18 /* shift, ctrl, alt */) || (evt.keyCode>=33 && evt.keyCode<=34 /* pgup, pgdwn */) || (evt.keyCode >= 37 && evt.keyCode <=40 /* left, up, right, down */) || (evt.keyCode == 224) /* command */))) {
				self.removeHighlight();
			}
		};
		this.textBox.onchange = function() { self.removeHighlight(); };
		
		this.busy = false;
	}
};

ultrapediaAPI_highlight.prototype.clearHighlightTimer = function() {
	if (this.highlightTimeout !== undefined) {
		window.clearTimeout(this.highlightTimeout);
		this.highlightTimeout = undefined;
	}
};

ultrapediaAPI_highlight.prototype.updateHighlight = function() {

	this.highlight.style.width = this.textBox.clientWidth + "px";
	this.highlight.style.top = Math.max(1, this.savedScrollTop - this.textBox.scrollTop + 1) + 'px';
	
	var topHeightLimiter = Math.max(0, this.highlightHeight + (this.savedScrollTop - this.textBox.scrollTop + 1));
	var bottomHeightLimiter = Math.max(0, this.textBox.offsetHeight - (this.savedScrollTop - this.textBox.scrollTop));
	
	this.highlight.style.height = Math.min(this.highlightHeight, Math.min(topHeightLimiter, bottomHeightLimiter)) + "px";
};

ultrapediaAPI_highlight.prototype.scrollHandler = function() {
	this.updateHighlight();
};

ultrapediaAPI_highlight.prototype.removeHighlight = function() {
	this.busy = true;
	
	fadeOut('ultrapedia-highlight', 7);

	if (typeof window.addEventListener != "undefined" ){
		window.removeEventListener("resize", ultrapediaAPI_highlight_highlightTimer, false );
	}
	else if (typeof window.attachEvent != "undefined" ) {
		window.detachEvent("onresize", ultrapediaAPI_highlight_highlightTimer );
	}
	
	this.textBox.onscroll = null;
	this.textBox.onkeyup = null;
	this.textBox.onchange = null;
};

/**
 * Run resize operation from timer as
 * IE7 doesn't allow DOM modification from onResize()
 */
function ultrapediaAPI_highlight_highlightTimer() {
	if (!ultrapediaAPI.busy) {
		ultrapediaAPI.clearHighlightTimer();
		ultrapediaAPI.highlightTimeout = window.setTimeout("ultrapediaAPI.highlightLine(0)", 500);
	}
};