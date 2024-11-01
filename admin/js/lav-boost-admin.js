(function( $ ) {
	'use strict';

	$(document).ready(function(){

		// Tabs in Order
		class Tabs {
			constructor(element) {
				this.tabs = element;
				this.toggles = this.tabs.querySelectorAll('.up-sell-pro-tabs .tabs__toggle');
				this.panels = this.tabs.querySelectorAll('.up-sell-pro-tabs .tabs__tab-panel')
			}
			init() {
				this.toggles.forEach((toggle, i) => {
					if(i === 0){
						this.toggles[i].classList.add('active');
						this.panels[i].classList.add('active');
					}
					toggle.addEventListener('click', (e) => {
						this.toggles.forEach(toggle => {
							toggle.classList.remove('active');
						})
						this.panels.forEach(panel => {
							panel.classList.remove('active');
						})
						e.target.classList.add('active');
						this.tabs.querySelector(`.up-sell-pro-tabs .tabs__tab-panel[data-tab='${e.target.dataset.tab}']`).classList.add('active')
					})
				})
			}
		}

		document.querySelectorAll('.up-sell-pro-tabs').forEach(tab =>{
			const tabs = new Tabs(tab);
			tabs.init();
		})
	})
})( jQuery );
