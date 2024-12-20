## Frequently Asked Questions

### Do I need to run the website on the same server?

No, if your web server has access to your OpenSimulator database.

### Can I use this plugin for my standalone simulator?

Yes, it works too. Use OpenSim database credentials when requested for Robust credentials.

### Why can't I change my avatar name?

This is an OpenSimulator design limitation. Regions rely on cached data to
display avatar information, and once fetched, these are never updated. As a
result, if an avatar's name (or grid URI btw) is changed, the change would not
be reflected on regions already visited by this avatar (which will still show
the old name), but new visited regions would display the new name. This could be
somewhat handled for a small standalone grid, but never in hypergrid context.
There is no process to force a foreign grid to update its cache, and probably
never will.

### Shouldn't I copy the helpers/ directory in the root of my webiste ?

No, you don't need to and you shouldn't. The /helpers/ is virtual, it is served
as any other page of your website. Like there the /about/ URL website doesn't
match a /about/ folder your webste directory. Even if there is a helpers/
directory in w4os plugin, it has the same name for convenience, but he could
have been named anything. It's content is not accessed directly, it is used by
the plugin to generate the answers. On the opposite, if there was an actual
helpers/ folder in your website root, it would interfer with w4os.

### Should I create assets/ directory in the root of my webiste ?

Yes and No. It can improve a lot the images delivery speed, but you won't
benefit of the cache expiry, which would eventually correct any wrong or
corrupted image.

### I use Divi theme, I can't customize profile page

Divi Theme support is fixed in versions 2.4.5 and above.

### I use OpenSimulator 0.8.x and...

Don't even finish that sentence. Versions of OpenSimulator older than 0.9
are definitely not supported anymore. You might be lucky and it might work, 
or not. In either case, idk&idc.
