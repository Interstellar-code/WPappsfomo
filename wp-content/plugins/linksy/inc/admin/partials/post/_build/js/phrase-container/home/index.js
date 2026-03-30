const LinksyPhraseHome = ({
    name: 'LinksyPhraseHome',
    components: {
        LinksySearch,
        LinksyPhraseCard,
        LinksyPhraseQuickApply
    },
    props: {
        items: {
            type: Array,
            default: []
        },
    },
    computed: {
        data: {
            get() {
                if (this.q.length > 2) {
                    return this.items.filter(e => e.phrase.toLowerCase().includes(this.q.toLowerCase()))
                }
                
                return this.items;
            }
        },
    },
    data() {
        return {
            q: '',
            mode: null,
            currentItem: null,
            selectedItems: [],
            styles: {
                onQuickApplyShown: {

                },
                onQuickApplyHidden: {
                    position: 'absolute',
                    left: '-200px'
                }
            }
        }
    },
    methods: {
        handleSearch(q) {
            this.q = q
        },

        handleSubmit() {
            this.$emit('onPhrasing', this.selectedItems);
        },

        onQuickApply(count) {
            let toApply = Math.min(count, this.items.length);

            if (toApply < 1) {
                return;
            }

            this.selectedItems = [];
            
            for (let i = 0; i < toApply; i++) {
                // get the document in first item
                loopj:
                for (let j = 0; j < (this.items[i]['documents']).length; j++) {
                    let document = this.items[i]['documents'][j]

                    // check wether its the first in others
                    for (let m = i + 1; m < toApply; m++) {
                        const nextDoc = this.items[m]['documents'][0]

                        if (document['post_id'] == nextDoc['post_id']) {
                            // if its the first compare the score
                            if (document['score'] < nextDoc['score']) {
                                continue loopj;
                            }
                        }
                    }

                    this.onItemSelect(document, this.items[i])
                    break;
                }
            }

            this.handleSubmit();

            this.$toast.success(`Best ${toApply} applied`);
        },

        onItemSelect(post, item) {
            const phraseItemIndex = this.selectedItems.findIndex(e => e.phrase === item.phrase);
            if (phraseItemIndex !== -1) {
                this.selectedItems[phraseItemIndex]['document'] = post;
            } else {
                this.selectedItems.push({
                    phrase: item.phrase,
                    source: item.source,
                    document: post
                });
            }

            this.$emit('onPhaseItemSelect', item.phrase, post);
        },
        onItemIgnore(post, item) {
            this.$emit('onPhaseItemIgnore', item.phrase, post);
        },
        onItemAdd(item, link) {
            this.$emit('onPhaseItemAdd', item.phrase, link);
        },
        onSelect(item) {
            if (this.currentItem && this.currentItem == item) {
                // already opened
            }

            this.currentItem = item;
            const itemIndex = this.items.findIndex(e => e.phrase == item.phrase);
            const el = document.getElementById('item-'+itemIndex);
            if (el) {
                // todo: scroll to top
                // todo: scroll to card
            }

            this.$emit('onPhaseSelect', {
                phrase : item.phrase
            });
        },
        onIgnore(item) {
            this.$emit('onPhaseIgnore', {
                phrase : item.phrase
            });
        },
    },
    template: `
        <div class="content">
            <div class="controllers" :class="{controlled: mode}">
                <div class="linksy-phrase quick-apply clickable" :class="{active: mode == 'quick-apply'}" @click="mode = 'quick-apply'">
                    <span style="padding-left: 12px;transition: .2s all linear;white-space:nowrap;" :style="[!mode || mode == 'quick-apply'? styles.onQuickApplyShown: styles.onQuickApplyHidden]">Quick Apply</span>
                    <i class="fa fa-chevron-right" style="padding-left: 14px; padding-right:12px; font-size: 14px;"></i>
                </div>

                <div class="linksy-phrase search" :class="{active: mode == 'search'}">
                    <linksy-search placeholder="Search" :initialValue="q" @on-change="handleSearch" @click="mode = 'search'"></linksy-search>

                    <i class="fa fa-times clickable" @click="mode=null;q='';"></i>
                </div>
            </div>

            <linksy-phrase-quick-apply
                v-if="mode == 'quick-apply'"
                @onCancel="mode = null"
                @onApply="onQuickApply"
            >
            </linksy-phrase-quick-apply>

            <linksy-phrase-card v-for="(item, index) in data"
                :item="item"
                :selected-items="selectedItems"
                :expanded="item.phrase === currentItem?.phrase"
                @onSelect="onSelect"
                @onIgnore="onIgnore"
                @onItemAdd="onItemAdd"
                @onItemSelect="onItemSelect"
                @onItemIgnore="onItemIgnore"
            >
            </linksy-phrase-card>
        </div>

        <div class="linksy-phrase-actions">
            <button type="button" class="btn-app" :disabled="selectedItems.length < 1" @click="handleSubmit"><i class="fa fa-check"></i>Apply</button>
        </div>
    `,
});