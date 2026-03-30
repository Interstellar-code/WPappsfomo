(function($) {
	const ID = 'hwt';
    const defaultConfigs = {};
	let LinksyHighlight = function($el, config) {
		this.init($el, config);
	};

	LinksyHighlight.prototype = {
		init: function($el, config) {
			this.$el = $el;
            this.config = {
                ...defaultConfigs,
                ...config
            }
            this.observer = null;

			this.generate();
		},

		generate: function() {
            const _this = this;
            const { post, phrase, parent } = this.config;

            const targetEl = this.$el.get(0);
            const {top, left, height} = targetEl.getBoundingClientRect();
            const parentDimensions = parent.getBoundingClientRect();
            
            let isTemplatOpened = false;
            let isTemplatFocused = false;
            let isTemplateEntered = false;
            let templateOpenedTimer = null;

            this.$el.on('mouseenter', function(e) {
                isTemplateEntered = true;
                $(this).css('background', 'rgba(0,0,0,.1)')
                templateOpenedTimer = setTimeout(function() {
                    if (!isTemplatOpened){
                        isTemplatOpened = true;

                        jQuery(parent).find('#linksy-phrase-highlights').empty();

                        const summaryTemplate = $(_this.cleanTemplate($('script[data-template="linksy-phrase-summary-template"]')).map(_this.renderTemplate({
                            title: post.post_title,
                            color: scoreToColor(post.score),
                            position: ordinalSuffix(post.position + 1),
                            rotation: 'rotate(' + (180 * (parseFloat(post.score) + Number.EPSILON)) + 'deg)',
                            percentile:  Math.round((parseFloat(post.score) + Number.EPSILON) * 100),
                            class: post.position > 0? 'positioned' : ''
                        })).join('')).css({
                            top: top + height - 2,
                            left: left
                        });

                        if ( left +  245  > parentDimensions.width) {
                            summaryTemplate.css('left', parentDimensions.width - 275)
                        }
                        
                        if ( top + height +  65  > parentDimensions.height) {
                            summaryTemplate.css({top: top - 142 })
                        }

                        jQuery(parent).find('#linksy-phrase-highlights').append(summaryTemplate);

                        summaryTemplate.on('mouseenter', function() {
                            isTemplatFocused = true;
                        }).on('mouseleave', function() {
                            isTemplatOpened = false;
                            isTemplatFocused = false;
                            summaryTemplate.remove();
                        });

                        summaryTemplate.find('h5').on('click', function() {
                            dispatchEvent('select', {
                                phrase : phrase,
                                post: post
                            });
                        });

                        summaryTemplate.find('.see-all').on('click', function() {
                            dispatchEvent('expand', phrase);
                        });

                        summaryTemplate.find('.ignore').on('click', function() {
                            dispatchEvent('ignore', phrase);
                        });
                    }
                }, 400);
            }).on('mouseleave', function() {
                isTemplateEntered = false;
                $(this).removeAttr( 'style' );
                if (templateOpenedTimer) {
                    clearTimeout(templateOpenedTimer);

                    if (!isTemplatFocused) {
                        let markerTimer = setTimeout(() => {
                            if (!isTemplatFocused && !isTemplateEntered) {
                                isTemplatOpened = false;
                                isTemplatFocused = false;
                                jQuery(parent).find('#linksy-phrase-highlights').empty();
                            }

                            clearTimeout(markerTimer);
                        }, 500)
                    }
                }
            });
            
			// plugin function checks this for success
			this.isGenerated = true;

            if (typeof MutationObserver != 'undefined') {
                this.observer = new MutationObserver((mutationList, observer) => {
                    for (let i = 0; i < targetEl.childNodes.length; i++) {
                        if (targetEl.childNodes[i].nodeType == Node.TEXT_NODE) {
                            const txt = targetEl.childNodes[i].nodeValue.trim();
                            if (txt) {
                                targetEl.style.borderBottomColor = txt == phrase? 'mark' : '#f3cf02';
                                break;
                            }
                        }
                        
                    }
                });
    
                this.observer.observe(targetEl, {
                    subtree: true,
                    characterData: true,
                });
            }
		},

        cleanTemplate: function(elem) {
            return elem.html().split(/\$\{(.+?)\}/g);
        },

        renderTemplate: function(props) {
            return function(tok, i) {
                return (i % 2) ? props[tok] : tok;
            };
        },

		destroy: function() {
            if (this.observer) {
                this.observer.disconnect();
            }

			this.$el
				.off(ID)
				.removeData(ID);
		},
	};

	// register the jQuery plugin
	$.fn.linksyHighlight = function(options) {
		return this.each(function() {
			let $this = $(this);
			let plugin = $this.data(ID);

			if (typeof options === 'string') {
				if (plugin) {
					switch (options) {
						case 'destroy':
							plugin.destroy();
							break;
						default:
							console.error('unrecognized method string');
					}
				} else {
					console.error(`you cannot ${options}, plugin must be instantiated first`);
				}
			} else {
				if (plugin) {
					plugin.destroy();
					console.log('plugin destroyed');
				}
				plugin = new LinksyHighlight($this, options);
				if (plugin.isGenerated) {
					$this.data(ID, plugin);
				}
			}
		});
	};
})(jQuery);