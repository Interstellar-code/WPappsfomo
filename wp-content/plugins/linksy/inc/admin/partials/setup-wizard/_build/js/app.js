const STEP_SYNC = 1;
const STEP_EMBBED = 2;

Vue.createApp({
    components: {
    },
    data() {
      return {
        step: 0,
        verification: {
            token: null,
            message: '',
            error: false,
            isSubmitting: false,
        },
        migration: {
            page: 0,
            message: '',
            progress: 0,
            error: false,
            last_page: null,
            isSubmitting: false,
        },
        anchor: {
            page: 0,
            message: '',
            progress: 0,
            error: false,
            last_page: null,
            isSubmitting: false,
        },
        keyword: {
            page: 0,
            message: '',
            progress: 0,
            error: false,
            last_page: null,
            isSubmitting: false,
        },
        embbedding: {
            message: '',
            progress: 0,
            error: false,
            complete: false,
            can_proceed: false,
        }
      }
    },
    watch: {
      step: function(val) {
        switch (val) {
            case STEP_EMBBED:
                this.embbed()
                break;
            case STEP_SYNC:
                if (!this.verification.token) {
                    this.$toast.error('Token not set', {
                        duration: 300,
                        onDismiss: () => window.location.replace(LINKSY.admin_url+'?page=Linksy-setup')
                    })
                }
                break;
            default:
                break;
        }
      }
    },
    methods: {
        async verify() {
            try {
                this.verification = {
                    ...this.verification,
                    message: '',
                    error: false,
                    isSubmitting: true,
                }

                const re = new RegExp('https?://(www\.)?', 'ig');
                const site_url = trim(LINKSY.site_url.replace(re, '').trim(), '/');

                await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_verify_plugin',
                        token: this.verification.token,
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });

                linkylyHandShake(this.verification.token, site_url, (res) => {
                    if(!res.success) {
                        throw new Error(res.message)
                    }

                    this.step += 1;
                    this.verification = {
                        ...this.verification,
                        isSubmitting: false,
                    }
                });
            } catch (error) {
                this.verification = {
                    ...this.verification,
                    error: true,
                    isSubmitting: false,
                    message: error.response?.data?.data || error.message
                }
            }
        },

        async migrate() {
            try {
                if (this.migration.error) {
                    this.step += 1;
                    return;
                }

                await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_setup_init',
                        token: this.verification.token,
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });

                this.migration = {
                    ...this.migration,
                    progress: 0,
                    isSubmitting: true,
                }

                do {
                    const {data: {data}} = await axios.get(ajaxurl, {
                        params: {
                            action: 'linksy_sync_posts',
                            page: this.migration.page,
                            nonce: LINKSY_SECURE_TOKEN,
                        }
                    });

                    this.migration = {
                        ...this.migration,
                        last_page: data.last_page,
                        page: this.migration.page + 1,
                        progress: Math.round((100 * (this.migration.page + 1)) / data.last_page)
                    }

                    if (data.failed.length > 0) {
                        this.$toast.warning("We found some errors, but you can continue")
                    }

                } while (this.migration.last_page !== this.migration.page);

                this.migration = {
                    ...this.migration,
                    error: false,
                }

                setTimeout(() => this.step += 1, 500);
            } catch (error) {
                this.migration = {
                    ...this.migration,
                    error: true,
                    message: error.message,
                    isSubmitting: false,
                }

                this.$toast.warning(error.message || "We found some errors, but you can continue");
            }
        },

        async generateLinks() {
            try {
                if (this.anchor.error) {
                    return;
                }
            
                this.anchor = {
                    ...this.anchor,
                    progress: 0,
                    isSubmitting: true,
                }
            
                do {
                    const {data: {data}} = await axios.get(ajaxurl, {
                        params: {
                            action: 'linksy_generate_links',
                            page: this.anchor.page,
                            nonce: LINKSY_SECURE_TOKEN,
                        }
                    });
            
                    this.anchor = {
                        ...this.anchor,
                        last_page: data.last_page,
                        page: this.anchor.page + 1,
                        progress: Math.round((100 * (this.anchor.page + 1)) / data.last_page)
                    }
            
                } while (this.anchor.last_page !== this.anchor.page);
            
                this.anchor = {
                    ...this.anchor,
                    error: false,
                    isSubmitting: false
                }
            } catch (error) {
                this.anchor = {
                    ...this.anchor,
                    error: true,
                    message: error.message,
                    isSubmitting: false,
                }
            
                this.$toast.warning(error.message || "We found some errors, but you can continue")
            }
        },

        async generateKeywords() {
            try {
                if (this.keyword.error) {
                    return;
                }
            
                this.keyword = {
                    ...this.keyword,
                    progress: 0,
                    isSubmitting: true,
                }
            
                do {
                    const {data: {data}} = await axios.get(ajaxurl, {
                        params: {
                            action: 'linksy_generate_keywords',
                            page: this.keyword.page,
                            nonce: LINKSY_SECURE_TOKEN,
                        }
                    });
            
                    this.keyword = {
                        ...this.keyword,
                        last_page: data.last_page,
                        page: this.keyword.page + 1,
                        progress: Math.round((100 * (this.keyword.page + 1)) / data.last_page)
                    }
                    
                } while (this.keyword.last_page !== this.keyword.page);
                    
                this.keyword = {
                    ...this.keyword,
                    error: false,
                    isSubmitting: false
                }
            } catch (error) {
                this.keyword = {
                    ...this.keyword,
                    error: true,
                    message: error.message,
                    isSubmitting: false,
                }
                    
                this.$toast.warning(error.message || "We found some errors, but you can continue")
            }
        },

        async reportErrors() {
            try {
                await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_report_errors',
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });
            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },

        async embbed () {
            this.embbedding= {
                ...this.embbedding,
                message: '',
                progress: 5,
                error: false,
                complete: false,
            }

            const checker = setInterval(() => {
                if (this.embbedding.progress < 95) {
                    const step = this.embbedding.progress > 70? 1 : 3;

                    this.embbedding = {
                        ...this.embbedding,
                        progress: this.embbedding.progress + step
                    };
                }

                if (this.embbedding.complete) {
                    clearInterval(checker);
                }
            }, 12000 * 3);

            try {
                await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_embbed_posts',
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });

                await this.generateLinks();

                await this.generateKeywords();

                this.embbedding= {
                    ...this.embbedding,
                    can_proceed: true,
                }

                await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_setup_safe',
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });

                await this.reportErrors();

            } catch (error) {
                this.embbedding = {
                    ...this.embbedding,
                    error: true,
                    complete: false,
                    message: error.message,
                };
            }
        },

        finish() {
            window.location.replace(LINKSY.admin_url+'?page=Linksy');
            return;
        }
    },
    mounted() {
        this.verification.token = document.getElementById('token')?.getAttribute('data-value')?.replace(' ', '');

        if (Boolean( (new URL(window.location)).searchParams.get("skip-verification"))) {
            this.step = STEP_SYNC;
        }

        socket.on("encode_finished", async (data) => {
            this.embbedding = {
                ...this.embbedding,
                message: '',
                error: data.status != 'error',
                progress: 100,
                complete: true,
            };
        });
    }
}).use(LinksyToast).mount('#setup-app');
