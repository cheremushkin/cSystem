<h2>Core Classes</h2>

Information about core classes.
Before you will edit files in this directory, please, read the information below.

<h3>Facade</h3>
Class <code>Facade</code> is a core of the system. It initializes most of the settings, loads a working class.
Some of it’s method <i>(for example, <code>Facade::traits()</code>)</i> can use an information from DB.

Includes two controllers: <code>Facade::index()</code> and <code>Facade::ajax()</code>. First is for <code>index.php</code>, second is for <code>ajax.php</code>.
Controllers launch a working class. This is the last action of <code>Facade</code> <i>(except printing a result)</i>.

<h3>Registry</h3>
Class <code>Registry</code> is a container for global variables. It uses pattern <code>Singleton</code>.



<h3>Builder</h3>
Class <code>Builder</code> provides safety and convenience variant of creating instances of classes.
Also it contains all information about classes that has been already created.



<h3>Proxy</h3>
Class <code>Proxy</code> can request information from different classes.
For example, if you want something from class <code>Foo</code>, you can make a request with <code>Proxy</code>.
In case if the method you’ve requested can be called from <code>Proxy</code>, you will receive information from it.
Direct call from one object to another don’t suitable for system’s ideology.
