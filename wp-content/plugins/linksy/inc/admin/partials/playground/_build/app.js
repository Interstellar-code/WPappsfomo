Vue.createApp({
    components: {
      KeywordSearch,
      LinksyTabBar,
    },
    data() {
        return {
            tab: 0,
            tabs: [
                {
                    id: 'keyword-search',
                    title: 'Keyword Search',
                }
            ],
        }
    },
    computed: {
        selectedTab: {
            get() {
                return this.tabs[this.tab];
            }
        }
    },
    watch: {
        tab: function(val) {
            console.log('tab changed:', val);
        }
    },
    methods: {
        handleTabSelect(index) {
            this.tab = index
        }
    },
    mounted() {
        console.log(this.selectedTab.id)
    }
}).mount('#playground-app');