module.exports = {
    '@tags': ['comments', 'page'],
    'Adding a comment as anonymous user': function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test','test')

            //create blog page to comment on
            /*.drupalRelativeURL('/node/add/article')
            .setValue('input[name="title[0][value]"]', 'blogtest')
            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Test Blog Page </p>"
                ]
            )

             */

            .drupalRelativeURL('/node/add/app')
            .setValue('input[id="edit-title-0-value"]', 'testapp')
            .setValue('input[id="edit-field-ribbon-0-target-id"]', 'PUBLIC (1041)')
            .setValue('select[id="edit-moderation-state-0-state"]','published')
            .submitForm('#edit-submit')
            .drupalLogout()
            .drupalRelativeURL('/app/testapp')

            //Add a comment

            .assert.containsText('body','Add new comment', 'Checking if comment field exists')

            .setValue('input[id="edit-name"]', 'testAnon')
            .setValue('#edit-comment-body-0-value','This is a test comment')
            .submitForm('#edit-submit')
            .assert.containsText('body','Your comment has been queued for review by site administrators and will be published after approval', 'Comment successfully submitted by non-admin user')

            .end();
    },

    'Approving a comment': function (browser) {
        browser.url(browser.launch_url)

            //Log in as admin and approve a comment
            .drupalLogin('test', 'test')
            .drupalRelativeURL('/admin/content/comment/approval')
            .click('link text', 'This is a test comment')
            .click('link text', 'Approve')
            .assert.containsText('body', 'Comment approved.', "Comment successfully approved")
            .drupalLogout()


            .end();
    },

    'Replying to a comment': function (browser) {
        browser.url(browser.launch_url)


            //Reply to a comment as a non-admin user
            .drupalRelativeURL('/blog/blogtest')
            .click('link text','Reply')
            .assert.urlContains('reply')
            //.element('css selector','#edit-name')
            .setValue('input[id="edit-name"]','testAnon2')
            .setValue('#edit-comment-body-0-value','This is a reply')
            .submitForm('#edit-submit')
            .assert.containsText('body','Your comment has been queued for review by site administrators and will be published after approval','Comment Reply successfully submitted')

            .end();
    },
    'Deleting a comment': function (browser) {
        browser.url(browser.launch_url)
            //Log in as admin and delete a comment
            .drupalLogin('test', 'test')
            .drupalRelativeURL('/admin/content/comment')
            .click('id', 'edit-comment-bulk-form-0')
            .click('id', 'edit-submit--2')
            .click('id', 'edit-submit')
            .assert.containsText('body', 'Deleted 1 comment.', "Comment Successfully deleted")
            .drupalLogout()
            .end();
    }


}