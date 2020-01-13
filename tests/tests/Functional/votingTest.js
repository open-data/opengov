module.exports = {
    '@tags': ['voting', 'page'],
    'Voting on an app': function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test', 'test')
            .drupalRelativeURL('/admin/structure/taxonomy/manage/app_ribbon/add')
            .setValue('input[id="edit-name-0-value"]', 'PUBLIC')
            .submitForm('#edit-submit')
            .assert.containsText('body','Created new term PUBLIC','Created new taxonomy term')

            //Create an app to vote on and logout
            .drupalRelativeURL('/node/add/app')
            .setValue('input[id="edit-title-0-value"]', 'appname')
            .setValue('input[id="edit-field-ribbon-0-target-id"]', 'PUBLIC')
            .setValue('select[id="edit-moderation-state-0-state"]', 'published')
            .click('id','edit-submit')
            .assert.containsText('body','App appname has been created','App successfully created')
            .drupalLogout()
            .drupalRelativeURL('/app/appname')

            //set rating and submit vote
            .click('id','rateit-range-2')
            .submitForm('#edit-actions-submit')
            .assert.containsText('body','Thank you for submitting your vote','Vote submitted')

            .end();
    },

    'Voting on a suggested dataset': function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test', 'test')

            //Create a suggested dataset to vote on and logout
            .drupalRelativeURL('/node/add/suggested_dataset')
            .setValue('input[id="edit-title-0-value"]', 'datasetname')

            .setValue('select[id="edit-moderation-state-0-state"]', 'published')
            .click('id','edit-submit')
            .assert.containsText('body','Suggested Dataset datasetname has been created','Suggested dataset created')
            .drupalLogout()
            .drupalRelativeURL('/suggestion/dataset/datasetname')

            //set rating and submit vote
            .click('button[name="op"]')
            .assert.containsText('body','Votes: 1','Vote submitted')

            .end();
    }
}
