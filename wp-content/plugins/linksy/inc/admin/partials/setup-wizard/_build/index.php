<div class="container shadow" id="setup-app" v-cloak>
  <div class="content">
    <div class="setup-nav">
      <div v-for="index in 3" :class="{active: step === index - 1}"></div>
    </div>

    <!-- Enter token -->
    <div v-if="step === 0">
        <div class="title">
          <h1>Verify your License</h1>
          <span>show us you paid for this</span>
        </div>

        <div>
          <input
            id="token"
            type="text"
            v-model="verification.token"
            class="w-full block my-1"
            data-value="<?php echo esc_attr($token); ?>"
            placeholder="Enter License"
          />
        </div>

        <button type="button" class="btn-app" :disabled="verification.isSubmitting" @click="verify()">Verify License</button>
        <small class="error" v-if="verification.error">{{verification.message}}</small>
    </div>
    <!-- end Enter token -->

    <!-- Sync Data -->
    <div v-if="step === 1">
        <div class="title">
          <h1>Crawl</h1>
          <span>prepare your posts for syncing</span>
        </div>


        <div class="shadow card">
          <div class="card-body d-flex justify-content-between">
            <div class="card-icon">
              <i class="fa fa-file-circle-check"></i>
            </div>
            <div>
              <h5 style="font-size: 12px; margin: 0; margin-bottom: 5px;">Published posts</h5>
              <h1 style="font-size: 16px; margin: 0;"><?php echo esc_html($posts_summary); ?></h1>
            </div>
          </div>
        </div>

        <br/>

        <div style="text-align: center;">
          <button
            type="button"
            :disabled="migration.isSubmitting"
            class="btn-outline-app"
            :class="{loading: migration.isSubmitting}"
            @click="migrate()"
          >
            {{migration.isSubmitting? migration.progress + '%' : migration.error? 'Continue': 'Crawl'}}
            <div :style="{width: migration.progress + '%'}"></div>
          </button>

          <small v-if="!migration.error && migration.last_page == migration.page">redirecting...</small>
        </div>
    </div>
    <!-- end Sync Data -->

    <!-- Success -->
    <div v-if="step === 2">
      <div class="title" style="margin-bottom: 10px;">
        <h1 style="margin-top:0">Sync Your Data</h1>
        <span>Let’s analyse your data</span>
      </div>
      <p>Syncing helps analyse your posts for optimal link suggestions. The process might take some time.</p>

      <div style="text-align: center; height: 20px;">
        <small class="error">{{embbedding.message}}</small>
      </div>

      <div class="circle-wrap-container">
        <div class="circle-wrap">
          <div class="circle">
              <div class="mask full" :style="`transform: rotate(${180/100 * embbedding.progress}deg);`">
                  <div class="fill" :style="`transform: rotate(${180/100 * embbedding.progress}deg);`"></div>
              </div>
              <div class="mask half">
                  <div class="fill" :style="`transform: rotate(${180/100 * embbedding.progress}deg);`"></div>
              </div>
              <div class="inside-circle"> {{embbedding.progress}}% </div>
          </div>
        </div>
      </div>

      <div v-if="anchor.isSubmitting || keyword.isSubmitting" style="text-align: center;" class="linksy-animation blink">Generating links: do not reload</div>
      
      <div v-if="embbedding.can_proceed">
          <button type="button" @click="finish()" class="btn-app">Go to Dashboard</button>

          <p class="py-2" v-if="!embbedding.complete">You can close this page, you don&apos;t have to wait.You will be alerted on the admin page!</p>
      </div>
    </div>
    <!-- end Success -->
  </div>

  <div class="info">
    <img src="https://linksyevents.plugli.com/public/images/setup-verify.png" />
  </div>
</div>