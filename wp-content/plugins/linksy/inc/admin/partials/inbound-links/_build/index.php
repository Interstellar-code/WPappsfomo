<?php if (isset($post_id)): ?>
<div class="container with-content" id="internal-links-app" v-cloak>
  <div>
    <div class="title-container d-flex flex-row align-items-center justify-content-between">
      <button class="back-button" @click="goBack">
        <i class="fa fa-chevron-left pe-2"></i>Back to Links Report
      </button>

      <div>
        <h2 class="title">Inbound Internal Link Suggestions</h2>
      </div>

      <a class="back-button clickable" :href="nextPostLink" :disabled="!nextPostLink">
        Next Orphaned<i class="fa fa-chevron-right ps-2"></i>
      </a>
    </div>

    <div class="search">
      <linksy-search cancelable predict :predictions="suggestions.map(e => e.title)" @on-change="(q, i) => navCustom(i)"></linksy-search>
    </div>

    <div class="suggestions">
      <div class="suggestions-selector">
        <div class="suggestions-selector-meta" v-if="suggestions.length > 0">
          <div class="suggestions-selector-meta-apply" style="display: inline-block;">
            <button @click="applySuggestions" :disabled="summary.length < 1 || isLoading">
              <span>Apply</span>
            </button>
            <quick-apply @on-submit="handleQuickSuggestionSelection" :value="summary.length"></quick-apply>
          </div>

          <div>
            <button @click="view = 'list'" :class="{'active': !isGridView}">
              <i class="fa fa-list-ul"></i>
            </button>
            <button @click="view = 'grid'" :class="{'active': isGridView}">
              <i class="fa fa-table-columns"></i>
            </button>
          </div>

          <div class="suggestions-selector-meta-pagination">
            <span v-if="isGridView">{{ currentIndex + 1 }} of {{ suggestions.length }}</span>
            <span v-else :style="{ paddingRight: '5px' }">Total: {{ suggestions.length }}</span>
            <div v-if="isGridView">
              <button :disabled="currentIndex < 1" @click="navPrev">
                <i class="fa fa-chevron-left"></i>
              </button>
              <button :disabled="currentIndex >= suggestions.length - 1" @click="navNext">
                <i class="fa fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="suggestions-selector-display" :class="view">
          <div v-for="(item, index) in suggestions" :class="{'active': item.active}" :id="'item-'+index" @click="navCustom(index)">
            <suggestion
              :item="item"
              :key="item.id"
              :is-grid-view="isGridView"
              @select="handleSuggestionSelection"
            >
            </suggestion>
          </div>
        </div>

        <div v-if="isLoading" class="loader suggestions-selector-loader">
          <i class="fa fa-spinner fa-spin"></i>
        </div>

        <div v-if="!isLoading && suggestions.length < 1" class="suggestions-selector-empty h-100">
            <div class="text-center">
                <i class="fa fa-hourglass-empty"></i>
                <h5>Nothing to show here</h5>
            </div>
        </div>
      </div>

      <div>
        <div class="table-summary">
          <div>
            <span @click="getSummary" style="color: #007AFF;"><?php echo $post_title; ?></span>
            <div class="actions">
              <a href="<?php echo esc_url($post_url); ?>" target="_blank">View</a>
              <a href="<?php echo esc_url($post_edit_url); ?>" target="_blank">Edit</a>
            </div>
          </div>
          <table>
            <tr>
              <td><i class="fa fa-arrow-down"></i></td>
              <td><i class="fa fa-arrow-up"></i></td>
              <td><i class="fa fa-arrow-up-right-from-square"></i></td>
            </tr>
            <tr>
              <td>{{inboundLinksCnt || '<?php echo esc_html($inbound_links); ?>'}}</td>
              <td>{{outboundLinksCnt || '<?php echo esc_html($outbound_links); ?>'}}</td>
              <td>{{externalLinksCnt || '<?php echo esc_html($external_links); ?>'}}</td>
            </tr>
          </table>
        </div>

        <div class="suggestions-summary">
          <h5>Summary</h5>

          <ul>
            <li>
              <div style="display: flex; align-items: center;">
                <div class="cube" style="background: rgb(16,185,129);"></div>
                <span>Great</span>
              </div>
              <span>{{summaryGreatCnt}}/{{summaryCnt.great}}</span>
            </li>
            <li>
              <div style="display: flex; align-items: center;">
                <div class="cube" style="background: rgb(14,165,233);"></div>
                <span>Good</span>
              </div>
              <span>{{summaryGoodCnt}}/{{summaryCnt.good}}</span>
            </li>
            <li>
              <div style="display: flex; align-items: center;">
                <div class="cube" style="background: rgb(255,165,0);"></div>
                <span>Average</span>
              </div>
              <span>{{summaryAverageCnt}}/{{summaryCnt.average}}</span>
            </li>
            <li>
              <div style="display: flex; align-items: center;">
                <div class="cube" style="background: rgb(255,60,32);"></div>
                <span>Poor</span>
              </div>
              <span>{{summaryPoorCnt}}/{{summaryCnt.poor}}</span>
            </li>
          </ul>
        </div>
      </div>

      
    </div>
  </div>

  <input type="hidden" id="post_ID" name="post_ID" value="<?php echo esc_attr($post_id); ?>" />
</div>
<?php else: ?>
<div class="container with-content" id="internal-links-empty-app" v-cloak>
    <div class="title-container">
        <h2 class="title">Inbound Internal Link Suggestions</h2>

        <span class="sub-title">
        Choose from the suggested posts below to send inbound links to.
        </span>
    </div>
    <br />
    <div class="position: relative;">
        <div class="loader position-absolute w-100 mt-5" style="z-index: 9" v-if="isLoading">
            <span class="pe-5">
                <i class="fa fa-spin fa-spinner"></i>
            </span>
        </div>

        <div class="orphaned-posts px-3 py-2 px-md-4 py-md-3">
            <div>
                <h5 class="d-none d-md-block"> <i class="fa fa-arrows-split-up-and-left"></i>&nbsp;Suggested Posts</h5>
                <div class="search">
                    <linksy-search cancelable placeholder="Search any post" @on-change="handleSearch"></linksy-search>
                </div>
            </div>
            <ul>
                <li v-for="item in posts">
                    <a :href="linksy_admin_url+'?page=Linksy-inbound-links&post_id='+item.ID">
                        {{item.post_title}}
                    </a>
                </li>

                <li v-if="!isLoading && posts.length < 1">
                    <span>Sorry, your search returns no result.</span>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>