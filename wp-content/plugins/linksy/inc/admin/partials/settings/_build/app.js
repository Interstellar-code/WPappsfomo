Vue.createApp({
    components: {
        Posts,
        General,
        Licensing,
        LinksyTab,
        LinksyTabBar,
    },
    data() {
        return {
            tab: 0,
            tabs: [
                {
                    id: 'general',
                    title: 'General Settings',
                },
                {
                    id: 'posts',
                    title: 'Post Settings',
                },
                {
                    id: 'licensing',
                    title: 'Licensing',
                }
            ],
            tabsMounted: false
        }
    },
    methods: {
        handleTabSelect(index) {
            this.tab = index
        },

        handleTabMounted() {
            this.tabsMounted = true;
        },

        saveSettings: async function(values, { setSubmitting }) {
            try {
                var params = urlSearchParams({
                    'action': 'linksy_settings_save',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'settings': JSON.stringify(values)
                });

                await axios.post(ajaxurl, params);

                this.$toast.success('Settings have been updated successfully');
            } catch (error) {
                this.$toast.error(error.response?.data?.data || error.message || "failed to save setting");
            } finally {
                setSubmitting(false);
            }
        }
    },
}).use(LinksyToast).mount('#settings-app');