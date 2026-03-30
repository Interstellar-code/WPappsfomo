
const LinksyPhraseQuickApply = ({
    name: 'LinksyPhraseQuickApply',
    data() {
        return {
            count: 2,
        }
    },
    methods: {
        onApply: function() {
            this.$emit('onApply', this.count);
            this.$emit('onCancel', this.count);
        }
    },
    template: `
        <!--linksy-phrase-quick-apply-->
        <div class="linksy-phrase linksy-phrase-quick-apply">
            <div class="linksy-phrase-quick-apply-body">
                <input type="number" v-model="count" />
                <div class="linksy-phrase-actions" style="margin-top:10px;padding-right:0;">
                    <button @click="$emit('onCancel')" class="btn-default"><i class="fa fa-times"></i>Cancel</button>
                    <button @click="onApply" class="btn-app"><i class="fa fa-check"></i>Apply</button>
                </div>
            </div>
        </div>
        <!--end-linksy-phrase-quick-apply-->
    `,
});