<div class="container with-content with-loader" id="dashboard-app" v-cloak>
    <div class="title-container align-items-stretch">
        <div class="d-flex align-items-center justify-content-between">
            <h2 class="title">Linksy Dashboard</h2>
            <button id="refresh" class="btn-outline-app" @click="resync">Resync</button>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-8">
            <linksy-post-stats
                :synced="postStats?.synced"
                :failed="postStats?.failed"
                :published="postStats?.published"
                :invalid="postStats?.invalid"
                @on-refresh="getPostSummary">
            </linksy-post-stats>
            <br />
            <linksy-anchor-cloud :items="anchors"></linksy-anchor-cloud>
            <br />
            <linksy-domains :items="domains"></linksy-domains>
        </div>
        <div class="col-12 col-md-4">
            <linksy-link-stats
                :orphaned="linkStats?.orphaned"
                :internal="linkStats?.internal"
                :external="linkStats?.external">
            </linksy-link-stats>
            <br />
            <linksy-keyword-rating :items="keywords"></linksy-keyword-rating>
        </div>
    </div>
</div>