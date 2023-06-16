# Troubleshooting

While this plugin aims to simplify your experience, it is important to keep in mind that it is a work in progress and relies on various components such as OpenSimulator, WordPress, PHP, and MySQL. With multiple dependencies involved, it's natural to encounter challenges along the way.

To help you address any issues you may encounter, we have put together a comprehensive checklist. This checklist will guide you through the necessary steps to identify and resolve most problems you may face while setting up and using the w4os plugin.

## Before installing w4os:

- Ensure Website Functionality: Make sure that your website is up and running smoothly without w4os installed.
- Verify OpenSimulator Grid/Standalone: Confirm that your OpenSimulator grid or standalone setup is functioning correctly.

If any issues arise with either of them, refer to their respective documentation for troubleshooting steps and resolve them before proceeding with the w4os installation.

If you have successfully completed the initial checks mentioned above and are still encountering issues with w4os, please proceed with the following troubleshooting steps. If the problem persists, don't hesitate to reach out for assistance. We are here to help you resolve any difficulties you may be facing.

If you need further assistance or want to seek help from the community, we recommend visiting [the w4os GitHub repository issues page](https://github.com/GuduleLapointe/w4os/issues/). There, you can find a wealth of information and solutions provided by other users who may have encountered similar issues. Sharing your experience can also contribute to helping others in the community.

If you decide to submit an issue, please ensure that you include the following information: the versions of w4os, WordPress, and PHP that you are using. Additionally, provide any relevant error messages from your web server log. These details will help us understand the context of the issue and assist you more effectively in resolving it.

## 1\. Check your web server error log

The first step in troubleshooting w4os issues is to review your web server error log. The web server error log contains valuable information that can help identify problems related to database connections or code errors. Follow these instructions to access and review your web server error log:

- Locate the web server error log on your server. The specific location may vary depending on your server configuration. Common paths include /var/log/apache2/error.log for Apache servers or /var/log/nginx/error.log for Nginx servers.
- Look for entries marked as ERROR. These lines often indicate critical issues that require attention. Pay close attention to them as they can provide insights into database connection problems or code errors.
- It's a good practice to examine lines marked as WARNING as well, but they are less likely to give relevant information in this context.

## 2\. Ensure the grid is up and running

Before proceeding with further troubleshooting, it is crucial to confirm that your OpenSimulator grid is operational. Follow these steps to ensure the grid is set up correctly:

1. Download the stable release of OpenSimulator from the official website: <http://opensimulator.org/wiki/Download>.
2. Follow the instructions provided on the website to set up your OpenSimulator grid. This typically involves installing OpenSimulator and configuring the necessary .ini files.
3. Once the setup is complete, start the grid and create your first avatar and your first region from the console.
4. Attempt to log in-world using your newly created avatar. Verify that you can successfully access the virtual environment and interact with the grid's features and objects.

By ensuring that your OpenSimulator grid is up and running properly, you can establish a solid foundation for troubleshooting any issues related to the w4os plugin. This step helps identify whether any problems you encounter are specific to the plugin or stem from the grid setup itself.

## 3\. Verify server requirements

To ensure smooth operation of the w4os plugin, it's important to verify that your server meets the following requirements:

- **Minimum required php version:** The minimum required PHP version for the plugin is **7.3**. While using PHP version 8.1 or later is recommended for adhering to general PHP best practices, it won't have a functional impact on the plugin itself.
- Install and enable the following PHP modules. If they are not included in the PHP core, you can use PECL to add them or install the appropriate packages for your system (e.g., php-xmlrpc and php-imagick on Linux).

  - **XMLRPC**: Required by most helpers and highly recommended for full WordPress functionality.
  - **Imagick**: required by profile and web assets server and highly recommended for full WordPress functionality.
  - Note: While it is possible to run w4os without XMLRPC or Imagick, it is strongly discouraged as it will result in the loss of essential functionalities, such as helpers and profile features.

- Take special URL settings into account, especially the Site Address URL. This can be found in the WordPress Admin > Settings > General section. If your Site Address URL is different from the default (e.g., "<https://yourgrid.org/wordpress/>" instead of "<https://yourgrid.org/>"), make sure to adjust the helpers address accordingly (e.g., "<https://yourgrid.org/wordpress/helpers/>").

- Ensure that permalinks are enabled in WordPress. Go to WordPress Admin > Settings > Permalinks and confirm that the Permalink structure is not set to "Plain". Saving any other choice should be sufficient, as w4os relies on the URL translation enabled by the Permalink structure.

## 4\. Review Admin > OpenSimulator > Settings

To continue troubleshooting, review the settings in the OpenSimulator section of your WordPress admin panel. Follow these steps:

- Access the OpenSimulator settings page by navigating to /wp-admin/admin.php?page=w4os_settings.
- Check the Login URI field and ensure that it is formatted correctly, such as "yourgrid.org:8002" without including the "http://" prefix.
- Verify that the Grid Name matches the Grid Name configured in your grid's .ini files. It's important to ensure this consistency even after saving the settings.
- Review your database credentials and ensure that no errors are displayed. Make sure the credentials are accurate and match your database configuration.
- If the option "Provide web profile page for avatars" is enabled (which is recommended), ensure that a corresponding "Profile" page exists in WordPress. This page should be created with the permalink set as profile. This allows avatars to have a web profile page associated with them.

By reviewing and adjusting these settings, you can ensure that the OpenSimulator configuration is correctly aligned with your grid setup and that the necessary features, such as avatar profiles, are properly enabled.

## 5\. Verify Admin > OpenSimulator > Helpers

To ensure proper configuration of the Helpers settings in the OpenSimulator section of your WordPress admin panel, follow these steps:

- Enable or disable the "Provide In-world Search" option:

  - if enabled,

    - Set the Search Engine URL to <http://yourgrid.org/helpers/query.php>, adjusting it with your Site Address as it appears in WP General settings. Make sure to use "http://" instead of "https://".
    - Set the Search register to <http://yourgrid.org/helpers/register.php>, adjusting it with your Site Address but using "[http://".By](http://”.By) using your own search engine, you will limit search results to your grid only, whether Hypergrid is enabled or not.

  - If not enabled and you use a third-party solution like w4os search engine, enter their address in the Search Engine URL field. Using an external provider will provide search results from all the grids registered with the same provider. This option is suitable if Hypergrid is enabled on your grid.Ensure that you enter the same addresses in the OpenSim.ini file to maintain consistency between the plugin settings and the OpenSimulator configuration.

- After saving the settings, access the Search Engine URL in a browser. It should display a blank page.

- Set the Events Server URL to "<http://2do.pm/events/>" or use any other implementation of the HYPEevents server.
- If the "Provide Offline Helper" option is enabled, set the Offline Helper URI to "<http://yourgrid.org/helpers/offline.php>". Make sure the configuration in both Robust.HG.ini and OpenSim.ini matches. Accessing the URI from your browser should result in a blank page.
- It is recommended not to enable the Economy feature until all other components are functioning correctly. However, if you have enabled the "Provide Economy Helpers" option, follow these steps:

  - Set the Economy Base URI to "<http://yourgrid.org/helpers/>", adjusting it with your actual Site Address and using "http://" instead of "https://".
  - Ensure that the configuration in Robust.HG.ini and OpenSim.ini files matches.
  - Accessing this URI should result in a blank page, indicating that the Economy Helpers functionality is set up correctly.Enabling the Economy feature allows for the integration of economic systems within your OpenSimulator grid. However, it's important to ensure that all other components are functioning correctly before enabling this option. By following the above steps, you can configure the Economy Base URI and verify its functionality within the w4os plugin.

If you have completed the above steps and the plugin is still not working, you can try using "http://" instead of "https://" in the OpenSim.ini file. This is not related to the plugin or WordPress but rather a limitation associated with certain .Net/mono versions used in OpenSimulator binaries. In some cases, the compiled version may not handle recent root certificates, even if they are legitimate. While it is possible to fix this by recompiling OpenSimulator with the correct root certificates, it can be a challenging process. Therefore, using "http://" (and "/helpers/") is often a more straightforward solution.

## 6\. Check the grid settings of the viewer

In addition to storing the grid login URI, the viewer also retains a set of URLs provided by the grid when it is added. These URLs include the services offered by the w4os plugin. It's important to note that if you make any changes to these URLs in your grid settings, the viewer will still use the previously stored values until you refresh them.

To ensure that the viewer reflects the updated URLs and services provided by the plugin, follow these steps:

1. Open the "OpenSimulator" or "Grids" tab in your viewer's Preferences. The specific name of this tab may vary depending on the viewer you are using.
2. Select your grid from the list of grids displayed.
3. Click on the "Refresh" button or a similar option available in the viewer. This action will update the stored URLs and services associated with your grid.

By refreshing the grid settings in your viewer, you ensure that any changes made to the URLs provided by the w4os plugin are recognized and utilized by the viewer. This step is crucial to ensure that the viewer is synchronized with the latest configuration of the plugin and its associated services.
