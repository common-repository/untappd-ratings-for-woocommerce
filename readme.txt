=== Untappd Ratings for WooCommerce ===
Contributors: Chillcode
Tags: untappd, woocommerce, ratings, reviews, map feed
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.6
WC requires at least: 6
WC tested up to: 8
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Everything you need to show **Untappd** ratings on **WooCommerce** stores.

== Description ==

**Untappd** is used by millions of users worldwide to check-in their beverages and this plugin serves as a helpful solution for bottle shops, breweries, brewpubs, beer shops, and anyone needing to connect **WooCommerce** and **Untappd**.

It enables them to display statistics on their sites effortlessly!

= Features =

* Show Untappd ratings instead of WooCommerce ones on single-product and loop pages.
* Sort products by Untappd ratings.
* Add an Untappd feed map to your site using Google Maps.
* Add Untappd ratings and reviews to Product Structured Data.
* Search beverages and breweries.

== Prerequisites ==

* [**WooCommerce**](https://github.com/woocommerce/woocommerce)

= Prerequisites for Using Untappd API =

To utilize the **Untappd API** (https://api.untappd.com/v4/), you need to have an [**Untappd** account](https://untappd.com/create) and obtain [**Untappd** API access](https://untappd.com/api/dashboard).

= Pricing =

**Free tier**: 100 API calls x hour.

No billing account required.

= Prerequisites for Adding an Untappd feedmap Using Google Maps API =

To utilize the **Google Maps API** (https://maps.googleapis.com), you must have a [**Google Cloud**](https://developers.google.com/maps/documentation/javascript/cloud-setup) project with a billing account.

To add an interactive map, you need to enable the [Maps Javascript API](https://developers.google.com/maps/documentation/javascript/overview).

To add a static map, you need to enable the [Maps Static API](https://developers.google.com/maps/documentation/maps-static/overview).

To learn more about Google Maps, visit [**Google Maps**](https://www.google.com/maps/about/#!/).

= Pricing =

**Google Cloud APIs** offer a free monthly tier of $200 USD. After this limit is reached, additional charges may apply. [Read more](https://developers.google.com/maps/billing-and-pricing/pricing)

== Installation ==

= Automatic =

1. **Access WordPress Admin**: Log in to your **WordPress** admin dashboard.
2. **Navigate to Plugins**: Once logged in, go to the "Plugins" section on the left-hand menu of the **WordPress** admin dashboard.
3. **Click on "Add New"**: Within the Plugins section, click on the "Add New" button. This will take you to the "Add Plugins" page.
4. **Search for the Plugin**: In the search bar on the top right, type in **Untappd ratings for WooCommerce**. **WordPress** will automatically search for plugins matching your search query.
5. **Find the Plugin**: Once you've found **Untappd ratings for WooCommerce**, click on the "Install Now" button below the plugin's name and description.
6. **Activate Plugin**: After the installation is complete, you'll see an "Activate" button. Click on it to activate the plugin on your **WordPress** site.

That's it! You've successfully installed and activated **Untappd ratings for WooCommerce** plugin automatically from within the **WordPress** admin dashboard.

= Manual =

1. **Download the Plugin**: Begin by downloading the plugin from **WordPress**. This is typically a zip file containing all the necessary files for the plugin.
2. **Access WordPress Admin**: Log in to your **WordPress** admin dashboard. This is usually accessed by adding "/wp-admin" to the end of your website's URL and entering your credentials.
3. **Navigate to Plugins**: Once logged in, go to the "Plugins" section on the left-hand menu of the **WordPress** admin dashboard.
4. **Click on "Add New"**: Within the Plugins section, click on the "Add New" button. This will take you to the "Add Plugins" page.
5. **Upload Plugin**: On the "Add Plugins" page, click on the "Upload Plugin" button at the top of the page.
6. **Choose File**: Click on the "Choose File" button and select the plugin zip file you downloaded in step 1 from your computer.
7. **Install Now**: After selecting the plugin file, click on the "Install Now" button. **WordPress** will now upload and install the plugin from the zip file.
8. **Activate Plugin**: Once the plugin is successfully installed, you will see a success message. Now, click on the "Activate Plugin" link to activate the plugin on your **WordPress** site.

That's it! You've successfully installed and activated **Untappd ratings for WooCommerce** plugin manually from within the **WordPress** admin dashboard.

== Configuration ==

1. **Access WordPress Admin**: Log in to your **WordPress** admin dashboard.
2. **Navigate to WooCommerce**: Once logged in, go to the **WooCommerce** section on the left-hand menu of the **WordPress** admin dashboard.
3. **Go to WooCommerce > Settings**: Once **WooCommerce** is selected, go to **Settings** section under **WooCommerce** menu admin dashboard.
4. **WooCommerce Settings**: Once on **WooCommerce** settings section switch to the **Products** tab and Scroll down to the **Reviews** section.
5. **WooCommerce Products Reviews Settings**:
    - **Enable reviews**:
         - **Enable product reviews**: Check this to enable reviews and to allow **Untappd ratings for WooCommerce** to override them.
         - **Show "verified owner" label on customer reviews**: This plugin ignores this setting, all reviews from **Untappd** are shown and data is not verified.
         - **Reviews can only be left by "verified owners"**: This plugin ignores this setting, all reviews from **Untappd** are shown and data is not verified.
    - **Product ratings**:
         - **Enable star rating on reviews**: Enables the star rating review option for reviews and allows **Untappd ratings for WooCommerce** to override them.
         - **Star ratings should be required, not optional**: Make star rating required for reviews.

    Go to [WooCommerce Product Reviews Help](https://woo.com/document/product-reviews/) to read more.

6. Press **Save changes** to apply the new settings.
7. **WooCommerce Settings**: Stay on **WooCommerce** settings section and switch to the **Untappd** tab.
8. **Untappd Ratings for WooCommerce Settings**:

    To find all settings values visit [Untappd API Dashboard](https://untappd.com/api/dashboard).

    - **Untappd API section**:
        - **Untappd API Client ID**: Your Client ID obtained from **Untappd** API Dashboard.
        - **Untappd API Client Secret**: Your Client Secret obtained from **Untappd** API Dashboard.
        - **API Url**: Endpoint for the Untappd API. Default is https://api.untappd.com/v4/.
        - **APP Name**: The name of your application as registered in the **Untappd** API access request.
        - **Cache time**: This plugin utilizes temporarily cached data. Enter an integer representing the number of hours the cached data will last. Default is 3 hours.
        - **Show "Powered by Untappd" logo**: Enable to display the Untappd logo in the footer of Storefront-based themes. Default is disabled.

    - **Untappd ratings configuration**:
        - **Use Untappd Ratings**: Override **WooCommerce** ratings system with **Untappd** ratings. Default is disabled.
        - **Sort using Untappd Ratings**: Enable sorting on loop-page by **Untappd** ratings. To enable sorting by ratings, URWC will add post meta data to all products. Default is disabled.
        - **Display Ratings Text**: Show text-based ratings alongside stars. Default is disabled.
        - **Display Total Ratings**: Show total ratings next to stars. Default is disabled.
        - **Structured Data**: Include **Untappd** ratings and check-in data in product structured data. Default is disabled.

    - **Untappd map**:
        - **Cache Status**:  If cache fails, this option is disabled to prevent excessive connections to the **Untappd** API. Default is enabled.
        - **Add Product Link**: Show a link to the Untappd review for products that have been reviewed on Untappd. Default is enabled.
        - **Show ratings/reviews**: Only show ratings and reviews to WP editors on **Google Maps** InfoWindows. Default is disabled.
        - **Apply disallowed and moderation words checks to Untappd data**: Checkins with WP disallowed words will not be shown and checkins with moderated words will only be shown to WP editors. Default disabled.
        - **Show disclaimer on infoWindows marker**: Display a disclaimer linked to the Untappd reporting page. Default is disabled.
        - **Untappd brewery search**: Use the selector to find a term and retrieve the brewery ID needed to add a shortcode.

9. Press **Save changes** to apply the new settings.
10. **Verify Installation**: That's it! Once configured, verify that the plugin is working correctly adding a map or a beverage id to the product meta.

== Usage ==

= Show Untappd Ratings on product page instead of WooCommerce one's = 

1.  [Edit the product](https://woo.com/document/managing-products/) you want to show **Untappd** ratings.
2. Navigate to [**The Product Data**](https://woo.com/document/managing-products/#product-data) meta box, then go to the **Untappd** section.
3. **Untappd** section:
    - **Untappd Beer Search**: Enter an **Untappd** beverage ID or a search term to find a beverage. Once found, select the beverage and update/save product. Use brewery name and beer name to find the beer faster and reduce the number of calls to the Untappd API. 
4. Check ratings on product page.

= Show Untappd Map = 

Edit any part of your site with your favorite editor and add a shortcode:

[urwc_untappd_map api_key="GOOGLE_API_KEY" brewery_id="73836" center_map="yes" height="500" max_checkins="300" zoom="4"]

You can find all shortcode options in the attribute table on the [**Untappd ratings for WooCommerce**](https://github.com/ChillCode/untappd-ratings-for-woocommerce#add-a-google-map-untappd-feed-to-your-site) GitHub repository.

Use the search function added in the **Untappd Settings** to find a term and retrieve the brewery ID required to add a shortcode.

Alternatively, to find the brewery ID for the Untappd brewery search shortcode, follow these steps:

1. Visit the Untappd website: https://untappd.com/.
2. Use the search bar at the top to search for the brewery you're interested in.
3. Once you find the brewery, click on its name to view its details.
4. In the URL of the brewery's page, you'll find a numerical value after "/brewery/" - this is the brewery ID.
5. Copy the brewery ID and use it when adding the shortcode.

For example, if the URL is https://untappd.com/w/brasserie-cantillon/202, then 202 would be the brewery ID to use in the shortcode.

== Screenshots ==

1. **Untappd ratings for WooCommerce** API Configuration.
2. **Untappd ratings for WooCommerce** Ratings Configuration.
3. **Untappd ratings for WooCommerce** Map Configuration.
4. The Product Data meta box **Untappd** section.
5. Untappd Brewery ID search.
6. How Structured Data is shown on search results.
7. How **Untappd** ratings are shown on product page.
8. **Untappd** map feed example with infowindow using pagination for checkins.

== Frequently Asked Questions ==

= Does Untappd ratings for WooCommerce work with any theme? = 

Can work with any theme but may require some additional fixing. We recommend **Storefront**.

= How to delete cached data?

Deactivate **Untappd ratings for WooCommerce** to delete cached (transients) data completely.

[Manage plugins](https://wordpress.org/documentation/article/manage-plugins/)

= How to delete all plugin data?

1. Backup **WordPress**.
2. Uninstall **Untappd ratings for WooCommerce** to erase all data completely, including post_meta data and **Untappd API** configuration.

[Manage plugins](https://wordpress.org/documentation/article/manage-plugins/)

==  Disclosure on use of 3rd Party and external services == 

This software make use of **Google Maps API** (https://maps.googleapis.com) and **Untappd API** (https://api.untappd.com/v4/) endpoints.

Every service has its own set of terms and conditions, as well as potential charges. To find out more about pricing and specific terms, please consult the provided links.

[**Untappd Terms of Use**](https://untappd.com/terms)

[**Google Maps Platform Terms of Service**](https://cloud.google.com/maps-platform/terms)

Your utilization of these services establishes a legal agreement between you (the end user of **Untappd ratings for WooCommerce**) and the respective service provider. We, in this context, are not a participant in this contractual agreement. We solely furnish the software facilitating access to their API, which operates on your servers and within your browser.

* We do not impose charges for accessing these APIs.
* We do not mediate API calls through our servers.
* We do not retain or handle your access credentials.
* We do not verify reviews or ratings, and we hold no accountability for their content nor authenticity.
* We hold no accountability for your actions regarding data usage.

== Copyright == 

[**WooCommerce**](https://wordpress.org/plugins/woocommerce/), [**Google Maps**](https://www.google.com/maps/about/#!/) and [**Untappd**](https://untappd.com) trademarks are the property of their respective owners.