const LinksyTabBar = ({
    name: 'LinksyTabBar',
    props: {
        title: String,
        persist: Boolean,
        tab: {
            type: Number,
            default: 0
        },
        tabs: {
            type: Array,
            default: []
        },
    },
    watch: {
        tab: {
            handler(val) {
                if (this.persist) {
                    const currentUrlArr = window.location.href.split('#');
                    window.location.href = currentUrlArr[0]+'#'+this.getId(this.tabs[val]).replace(/ /g, "-").replace(/@/g, "").replace(/\$/g, "").replace(/!/g, "").replace(/#/g, "").toLowerCase();
                }
            }
        }
    },
    computed: {
        currentTab: {
            get() {
                return this.tab;
            }
        }
    },
    methods: {
        getId(tab) {
            return typeof tab == 'string'? tab : tab.id;
        },
        getTitle(tab) {
            return typeof tab == 'string'? tab : tab.title;
        }, 
        onSelect(index) {
            this.$emit('onSelect', index)
        }
    },
    template: `
        <div class="tabbar">
            <h5 v-if="title">{{title}}</h5>
            <ul style="display: flex; margin: 0;">
                <li v-for="(tab, index) in tabs" :class="{ 'active': currentTab == index }" @click="onSelect(index)">
                    <slot :tab="tab" :index="index" :active="currentTab == index">
                            <button>{{getTitle(tab)}}</button>
                    </slot>
                </li>
            </ul>
        </div>
    `,
    mounted() {
        if (this.persist) {
            const currentUrlArr = window.location.href.split('#');
            if (currentUrlArr.length > 1) {
                const currentTabUrl = currentUrlArr[1];

                for (let index = 0; index < this.tabs.length; index++) {
                    const tab = this.tabs[index];
                    if ( currentTabUrl == this.getId(tab).replace(/ /g, "-").replace(/@/g, "").replace(/\$/g, "").replace(/!/g, "").replace(/#/g, "").toLowerCase() ) {
                        this.onSelect(index);
                        break;
                    }
                }
            }
        }

        this.$emit('onTabMounted');
    }
});