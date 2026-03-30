const LinksyTab = ({
    name: 'LinksyTab',
    props: {
        tab: {
            type: Number,
            default: 0
        },
        tabs: {
            type: Array,
            default: []
        },
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
    template: `
        <div v-if="tabs.length > 0" class="tab-content">
            <slot :currentTab="selectedTab" :currentTabIndex="tab">
                {{selectedTab.title || selectedTab.id}}
            </slot>
        </div>
    `
});