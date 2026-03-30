const LinksyKeywordRating = ({
    name: 'LinksyKeywordRating',
    props: {
        items: [Array, Object],
    },
    data() {
        return {
            to: LINKSY.admin_url+'?page=Linksy-keywords-rating'
        }
    },
    computed: {
        loading: {
            get() {
                const itemsWithScores = this.items.filter(e => e.score)
                return itemsWithScores.length < 5001;
            },
        },
        totalAvg: {
            get() {
                const totalScore = this.items.map(e => e.score).reduce((prev, current) => parseFloat(prev) + parseFloat(current), 0);
                return Math.round(((totalScore / this.items.length) + Number.EPSILON) * 100)
            },
        },
        greatCnt: {
            get() {
                const i = this.items.filter(e => e.tag === 'great');
                return i.length;
            },
        },
        goodCnt: {
            get() {
                const i = this.items.filter(e => e.tag === 'good');
                return i.length;
            },
        },
        averageCnt: {
            get() {
                const i = this.items.filter(e => e.tag === 'average');
                return i.length;
            },
        },
        poorCnt: {
            get() {
                const i = this.items.filter(e => e.tag === 'poor');
                return i.length;
            },
        }
    },
    methods: {
        getPercentile(score) {
            score = parseFloat(score);
            return Math.round(((score / this.items.length) + Number.EPSILON) * 100)
        },
        getColor(tag) {
            return tagToColor(tag);
        },
    },
    template: `
        <div class="card shadow linksy-keyword-rating">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Keyword Rating</h2>
                    <a class="button btn-app" :href="to" :disabled="items.length < 1">See all</a>
                </div>

                <div class="linksy-keyword-rating-items">
                    <div class="d-flex justify-content-between border-bottom align-items-center">
                        <h5>Overall Score/Rating</h5>
                        <h5 v-if="items.length">{{totalAvg}}</h5>
                    </div>

                
                    <div class="d-flex justify-content-between align-items-center border-bottom">
                        <h5>
                            <div class="cube" :style="{background: getColor('great')}"></div>
                            <span>Great</span>
                        </h5>

                        <h5 v-if="items.length">{{greatCnt}}&nbsp;({{ getPercentile(greatCnt) }}%)</h5>
                        <h5 v-else>Calculating</h5>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-bottom">
                        <h5>
                            <div class="cube" :style="{background: getColor('good')}"></div>
                            <span>Good</span>
                        </h5>

                        <h5 v-if="items.length">{{goodCnt}}&nbsp;({{ getPercentile(goodCnt) }}%)</h5>
                        <h5 v-else>Calculating</h5>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-bottom">
                        <h5>
                            <div class="cube" :style="{background: getColor('average')}"></div>
                            <span>Average</span>
                        </h5>

                        <h5 v-if="items.length">{{averageCnt}}&nbsp;({{ getPercentile(averageCnt) }}%)</h5>
                        <h5 v-else>Calculating</h5>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <h5>
                            <div class="cube" :style="{background: getColor('poor')}"></div>
                            <span>Poor</span>
                        </h5>

                        <h5 v-if="items.length">{{poorCnt}}&nbsp;({{ getPercentile(poorCnt) }}%)</h5>
                        <h5 v-else>Calculating</h5>
                    </div>
                </div>
            </div>
        </div>
    `
});
