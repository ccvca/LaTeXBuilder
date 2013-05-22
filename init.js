var debug;
/*
 * @copyright 2013
 * @author ccvca https://github.com/ccvca/
 * @licence http://creativecommons.org/licenses/by-nc-sa/3.0/de/ (EN: http://creativecommons.org/licenses/by-nc-sa/3.0/) 
 */
(function(global, $){

	$(function(){
		codiad.latexbuild.init();
	});
	
	var lastLine = null;
	var lastPath = null;

	var pdfLoadedCheck = null;
	var pdfBuildCheck = null;
	
	self = null;
	
	function htmlEntities(str) {
	    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
	
	global.codiad.latexbuild = {

		latexBuildDir:'plugins/latexbuild/',
		pdfjsPath:'/pdf.js/web/viewer.html',
		codiadURL: null, //Will be initialised in init()
		pdfPath: null, //Will be initialised in init()
		
		//pdf.js popup
		pdfView: null,
		
		init : function(){
			self = this;
			// Ctrl + B
			$.ctrl('66', function(){
				self.buildPDF();
			});
			
			amplify.subscribe('active.onFocus', this.ActiveOnActive);
			//CodiadDir for f.e. /codiadLaTeX without a slash at the end
			this.codiadURL = global.location.pathname.substr(0, global.location.pathname.lastIndexOf('/'));
			
			this.pdfPath = this.latexBuildDir+'getPDF.php';
		},

		termWidth : $(window).outerWidth() - 500,

		open : function(){
			codiad.modal.load(this.termWidth,
					this.latexBuildDir+'dialog.php');
			codiad.modal.hideOverlay();
		},

		setMainTex : function(){
			if(codiad.active.getPath() == null){
				codiad.message.error('No active file.');
			}else{
				$.post(self.latexBuildDir+"process.php", {
					'action' : 'setMainTex',
					'filename' : codiad.active.getPath()
				}, this.showStatus);
			}
			
			codiad.modal.unload();
		},

		buildPDF : function(){
			$.post(self.latexBuildDir+"process.php", {
				'action' : 'buildPDF'
			}, function(oresp){
				resp = $.parseJSON(oresp);
				if (resp.status == 'success') {
					//Look when it's finish
					pdfBuildCheck = setInterval(function(){
						$.post(self.latexBuildDir+"process.php", {
							'action' : 'checkRunning'
						}, function(resp){
							resp = $.parseJSON(resp); 
							if(resp.status == 'notice'){
								//process finished
								clearInterval(pdfBuildCheck);
								self.checkErrors();
							}
						});
						
					}, 2000);
				}
				self.showStatus(oresp);
			});

			codiad.modal.unload();
		},
		
		//check if errors occure
		checkErrors : function(){
			$.post(this.latexBuildDir+"process.php", {
				'action' : 'getLaTeXErrors'
			}, function(oresp){
				resp = $.parseJSON(oresp);
				self.showStatus(oresp);
				if(resp.status == 'warning'){
					var width = 500;
					//show LaTeX errors in a div
					$('#modal').css({
	                    'bottom': '20px',
	                    'top': 'auto',
	                    'left': '50%',
	                    'min-width': width + 'px',
	                    'margin-left': '-' + Math.ceil(width / 2) + 'px'
	                }).draggable({ handle: '#drag-handle'});
					$('#modal-content').html(self.LaTeXErrorsToHTML(resp.errors));
	                // Fix for Firefox autofocus goofiness
	                $('input[autofocus="autofocus"]').focus();
	                $('#modal').fadeIn(200);
				}
			});
			
		},
		
		LaTeXErrorsToHTML: function(errors){
			var ret = '<div id="LaTeXerrorDiv"><table>';
			ret += '<thead><th>Path</th> <th>Line</th> <th>Error (Doubleclick to go to the file)</th></thead>';
			//debug = errors;
			for(indx in errors){
				var path = errors[indx].relPath === null ? errors[indx].absPath : errors[indx].relPath;
				var isAbsPath = (errors[indx].relPath === null);
				//files out of the workspace can't be opend
				if(isAbsPath){
					ret += '<tr>';
				}else{
					ret += '<tr ondblclick="codiad.latexbuild.gotoFile(\''+path+'\', '+errors[indx].line+');" >';
				}
					ret += '<td>';
						ret += htmlEntities(path);
					ret += '</td>';
					ret += '<td>';
						ret += errors[indx].line;
					ret += '</td>';
					ret += '<td>';
						ret += htmlEntities(errors[indx].errormsg);
					ret += '</td>';
				ret += '</tr>';
			}
			ret += '</table><button onclick="codiad.modal.unload();">Close</button></div>';
			return ret;
		},

		checkRunning : function(){
			$.post(this.latexBuildDir+"process.php", {
				'action' : 'checkRunning'
			}, this.showStatus);

			codiad.modal.unload();
		},

		showStatus : function(resp){
			resp = $.parseJSON(resp);
			switch (resp.status){
				case 'success':
					codiad.message.success(resp.msg);
					break;
				case 'warning':
					codiad.message.warning(resp.msg);
					break;
				case 'error':
					codiad.message.error(resp.msg);
					break;
				case 'notice':
					codiad.message.notice(resp.msg);
					break;
			};
		},
		
		openPDF: function(){
			//height=pixels,
			var popup = window.open(this.pdfjsPath + '?file='+ this.codiadURL+ '/' + this.pdfPath,
					'latexbuildPdfView', 'width=800, right=15, location=no, menubar=no, status=no');
			
			
			//see pdf.js/web/viewer.js:1369
			//popup.document.addEventListener('pagerender', this.SetSyncTeXClickEventsInPDF , false);
			
			codiad.modal.unload();
			
			this.pdfView = popup;
			if(pdfLoadedCheck != null){
				window.clearInterval(pdfLoadedCheck);
			}
			pdfLoadedCheck = window.setInterval(this.checkPDFLoaded(), 1000);
		},
		
		checkPDFLoaded : function(){
			return function(){
				if(self.pdfView == null){
					window.clearInterval(pdfLoadedCheck);
					pdfLoadedCheck = null;
					console.log('PDF loading breaked.');
					return;
				}
				
				if(self.pdfView.PDFView == undefined){
					//not loaded
					console.log('Wait for loading PDF viewer...');
					return;
				}
				
				if(!self.pdfView.PDFView.loading){
					window.clearInterval(pdfLoadedCheck);
					pdfLoadedCheck = null;
					self.SetSyncTeXClickEventsInPDF();
					console.log('Hooked into pdf.js viewer');
				}
			};
		},
		
		SetSyncTeXClickEventsInPDF: function(){
			
			if(this.pdfView == null){
				codiad.message.error('No PDF viewer availiable.');
				return;
			}
			
			var viewer = self.pdfView.document.getElementById('viewer');
			
			if(!viewer){
				codiad.message.error('No PDFviewer availiable.');
				return;
			}
			
			var pages = viewer.getElementsByClassName('page');
			debug = pages;
			
			for(var i=0; i < pages.length; ++i){
				pages[i].addEventListener('click', self.SyncTeXClickEv(pages[i], self.pdfView), false);
			}
			
			self.pdfView.mpa = global;
		},
		
		
		//return function for eventHandling
		SyncTeXClickEv : function(pageDiv, viewWindow){
			return function(ev){
				//@var pageDiv contains the div of the page
				
				//Only alt+click is handeld
				if(!ev.altKey){
					return;
				}
				
				//ev.target doesn't work if there are overlaying div's
				var offs = $(pageDiv.getElementsByTagName('canvas')[0]).offset();
				
				var rx = (ev.pageX - offs.left );
				var ry = (ev.pageY - offs.top);
				
				debug = ev;
				//pageContainer1 --> 1
				var pageNum = pageDiv.id.substr('pageContainer'.length );
				//PDFView.getPage(1).data.view
				var page = viewWindow.PDFView.getPage(pageNum);
				
				//The full page width and height in 72dpi
				var pageWidth = page.data.view[2];
				var pageHeight = page.data.view[3];
				
				//calc scale, from the css-Values the "px" must be removed
				var scaleX = pageWidth / pageDiv.style.width.substr(0, pageDiv.style.width.length-2);
				var scaleY = pageHeight / pageDiv.style.height.substr(0, pageDiv.style.height.length-2);
				
				//codiad.message.notice('SyncTex Event: realX:' + (scaleX*rx) + '  realY: '+ (scaleY*ry));
				
				//real click position in 72dpi
				var realX = scaleX * rx;
				var realY = scaleY * ry;
				
				//get the File out of SyncTex
				$.post(self.latexBuildDir+"process.php", {
					'action' : 'SyncTeXGetFile',
					'realX' : realX,
					'realY' : realY,
					'pageNum'  : pageNum
				}, self.processSyncTexGetFile);
				viewWindow.blur();
			};
		},
		
		processSyncTexGetFile: function(oresp){
			resp = $.parseJSON(oresp);
			if(resp.status == 'success'){
				if(resp.relative != ''){
					/*codiad.filemanager.openFile(resp.relative);
					
					lastLine = resp.line;
					lastPath = resp.relative;*/
					codiad.message.notice('SyncTex: Opening file...');
					self.gotoFile(resp.relative, resp.line);
				}else{
					codiad.message.notice("SyncTex: File is not in workspace.");
				}
			}else{
				self.showStatus(oresp);
			}
		},
		
		gotoFile: function(relPath, line){
			codiad.filemanager.openFile(relPath);
			lastLine = line;
			lastPath = relPath;
		},
		
		//Event listener
		ActiveOnActive: function(path){
			//amplify.subscribe('active.onFocus', this.ActiveOnActive);
			if(lastPath != null && path == lastPath){
				codiad.editor.gotoLine(lastLine, codiad.editor.getActive());
				lastPath = null;
				lastLine = null;
			}
		}/*,
		
		getLaTeXErrors: function(){
			//getLaTeXErrors
			$.post(self.latexBuildDir+"process.php", {
				'action' : 'getLaTeXErrors'
			}, this.showStatus);
		}*/

	};
})(this, jQuery);