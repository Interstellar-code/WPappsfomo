const { createApp } = Vue;

const app = createApp({
    components: {
        LinksyTab,
        LinksyTabBar,
        DomainReport,
        InternalLinksReport,
    },
    data() {
        return {
            tab: 0,
            tabs: [
                {
                    id: 'internal-links-report',
                    title: 'Internal Links Report',
                },
                {
                    id: 'domain-report',
                    title: 'Domains Report',
                }
            ],
            tabsMounted: false,
        }
    },
    methods: {
        handleTabSelect(index) {
            this.tab = index
        },

        handleTabMounted() {
            this.tabsMounted = true;
        }
    },
});

app.mount('#reports-app');