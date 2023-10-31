<article class="markdown-body entry-content container-lg" itemprop="text">

<h1 tabindex="-1" dir="auto">
Drupal 9 Test Plan for Administrators
</h1>

<h2 tabindex="-1" dir="auto">
🟩 User-Account: Editing account information and viewing submissions. 
</h2>

<ol>
<li class="task-list-item">
  <h3>Editing an accounts information and permissions - In the "Edit" option within a User Account page, you should be able to:</h3>
  <ul>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "First Name"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Specify "Your current logout threshold" for an account - <i>Log out and test you cannot login again until the specified time has passed</i>
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Last Name"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Email Address"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Username"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Password"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Status" - <i>Block an account and test the account cannot login, unblock and the account shall login</i>
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Roles" - <i>Test that each role modifies the accounts permissions to the permissions granted by the new role</i>
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Check that you apply "Cancel account" to an account- <i>Delete the account</i>
    </li>
    
  </ul>
</li>
<li>
  <h3>Reviewing the content submissions - In the "Submissions" option within a User Account page, you should be able to:</h3>
  <ul>
    <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      See all the content the account has created and/or modified, ordered from most recent date of modification.
    </li>
  </ul>
</li>
</ol>

<h2 tabindex="-1" dir="auto">
🟥 System Commands:
</h2>
<ol>
  
  <li>
    <h3>Under the blue Drupal logo you should be able to:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush all caches
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush CSS and JavaScript 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush plugins cache
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush render cache
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush routing and links cache
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush static cache
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush twig cache
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush views cache
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Rebuild theme registry
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Run Chron 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Run updates
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Index - Under the blue Drupal logo in the "Index" tab, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      *list administative tasks for each module?*
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟦 Content: Filtering, creating and managing content
</h2>
<ol>
  
  <li>
    <h3>Searching, Filtering and Actions on content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Filter, Search and apply Actions on content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Content type" to filter 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Moderation state" to filter
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Language" option to filter
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter a "Title" to search for
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Apply an "Action" to one or more content items - <i>Change moderation state, Delete content or Update URL Alias</i>
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Adding Content - In the "Content" tab, select the "Add content" options you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create an "App"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Basic Page"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Blog Post"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Commitment"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Consultation"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "External"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Idea"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Landing page"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Map"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Open Data Impact Story"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Suggested Dataset"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Theme and Topic page"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Webform"
      </li>
    </ul>
  </li>

  <li>
    <h3>Adding Media - In the "Content" tab, select the "Media" and "Add Media" options, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Add some "Audio"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Add a "Document"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Add an "Image"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Add a "Remote video"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Add a "Slideshow item"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Add a "Video"
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟧 Structure: 
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟪 Appearance:
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟨 Extend:
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟫 Configuration:
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟥 People:
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟪 Reports:
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>
