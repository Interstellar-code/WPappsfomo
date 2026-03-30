Vue.createApp({
    components: {
        LinksyDomains,
        LinksyPostStats,
        LinksyLinkStats,
        LinksyAnchorCloud,
        LinksyKeywordRating,
    },
    data() {
        return {
            postStats: null,
            linkStats: null,
            anchors: {},
            domains: [],
            keywords: [],
        }
    },
    methods: {
        async resync() {
            try {
                if (confirm('This will rerun the setup page. Continue?')) {
                    this.$toast.info('redirecting', {
                        duration: 500,
                        onDismiss: () => window.location.replace(LINKSY.admin_url+'?page=Linksy-setup&skip-verification=1')
                    })
                }
            } catch (error) {
                this.$toast.error(error.response?.data?.data || error.message)
            }
        },
        async getPostSummary() {
            try {
                const {data: {data: {failed, synced, published, invalid}}} = await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_dashboard_get_post_summary',
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });

                this.postStats = {
                    failed,
                    synced,
                    published,
                    invalid
                }

                LinksyStorage.set('dashboard_post_summary', this.postStats);

            } catch (error) {
                this.postStats.error = true;
                console.log(error.response?.data?.data || error.message);
            }
        },
        async getLinksSummary() {
            try {
                const {data: {data: {stats, anchors, domains}}} = await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_dashboard_get_links_summary',
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });
                
                this.anchors = anchors;
                this.domains = domains;
                this.linkStats = stats;

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },
        async getKeywordsSummary() {
            try {
                const {data: {data}} = await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_dashboard_get_keywords_rating',
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });

                this.keywords = data.filter(e => e.score).map(e => {
                    return {
                        score: e.score,
                        tag: scoreToTag(e.score),
                    }
                });

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },
    },
    beforeMount() {
        this.postStats = LinksyStorage.get('dashboard_post_summary');

        this.getPostSummary();
        this.getLinksSummary();
        this.getKeywordsSummary();
    },
}).use(LinksyToast).mount('#dashboard-app');