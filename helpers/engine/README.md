# OpenSimulator Engine

![Version 3.0.0-dev](https://badgen.net/badge/Version/3.0.0-dev/blue)
![Stable none](https://badgen.net/badge/Stable/none/green)
![Requires PHP 7.4](https://badgen.net/badge/PHP/7.4+/7884bf)
![License AGPLv3](https://badgen.net/badge/License/AGPLv3/552b55)

**Framework-agnostic PHP library for OpenSimulator grid management**

## ⚠️ Important Notice

**This is a pure PHP library - it does nothing by itself!**

The OpenSimulator Engine provides core functionality for managing OpenSim grids, but requires a parent application to function. It handles database operations, configuration management, and OpenSim protocol communication, but provides no user interface or web endpoints.

## 📜 Project History

This library consolidates **over a decade of OpenSimulator integration work** that was previously scattered across multiple projects. While the dedicated engine repository is recent, the functionality has evolved through years of real-world usage in production OpenSim grids.

The code has been **battle-tested** across different implementations before being organized into this reusable, framework-agnostic library.

## 🎯 What This Library Does

- ✅ **Database Operations** - Robust/OpenSim database management
- ✅ **OpenSim Protocol** - REST API communication with grids
- ✅ **Configuration Management** - Grid and region settings management and storage
- ✅ **Security Functions** - Input validation and output escaping
- ✅ **Form Generation** - Dynamic configuration forms
- ✅ **Installation** - Process setup automation (initiated by parent)

## 🚫 What This Library Does NOT Do

- ❌ No web interface or HTML pages
- ❌ No user HTTP request handling
- ❌ No user authentication
- ❌ No WordPress or other CMS/framework dependencies
- ❌ No standalone application functionality



### **For Developers:**
- Use this engine to build your own OpenSim management applications
- Integrate OpenSim functionality into existing PHP projects
- Create custom grid administration tools

## 🚀 Quick Start for End Users

**Don't install this directly!** Instead, choose a complete solution:

- **[W4OS WordPress Plugin](https://github.com/GuduleLapointe/w4os/)** - Complete WordPress integration for OpenSim grids. **Best for:** Complete integration in a WordPress website.
- **[OpenSim Helpers](https://github.com/magicoli/opensim-helpers)** - Provides mainly helpers/ required by OpenSim grids to function properly, as well as minimal webui features. **Best for:** Separate helpers management, with minimal integration with the website.

## 🛠️ Developer Installation

### As Composer Package
```bash
composer require magicoli/opensim-engine
```

### As Git Submodule
```bash
git submodule add https://github.com/magicoli/opensim-engine.git engine
```

### Usage in Code
```php
// Bootstrap the engine
require_once 'engine/bootstrap.php';
// or require_once 'vendor/magicoli/opensim-engine/bootstrap.php' if installed via composer

// Engine provides the core classes and functions
// See parent projects (W4OS, Helpers) for implementation examples
```

## 📚 Documentation

- **[Developer Guide](DEVELOPERS.md)** - Architecture rules and patterns
- **API Reference** - (planned after migration completion)
- **Examples** - (planned after migration completion)

## 🤝 Contributing

This library follows strict architectural principles:
- Framework-agnostic (no WordPress, no Laravel, etc.)
- Pure data processing (no HTTP input/output)
- Explicit data passing (no global variables)

See [DEVELOPERS.md](DEVELOPERS.md) for complete guidelines.

**Note:** The library is undergoing architectural migration. API documentation and examples will be added once the refactoring is complete and the API is stable.

## 📄 License

AGPLv3 - See [LICENSE](LICENSE) file for details.

## 🆘 Support

- **End Users:** Get support from the project using this engine (W4OS, Helpers)
- **Developers:** Create issues for bugs or feature requests https://github.com/magicoli/opensim-engine/issues
- **Documentation:** Check the parent project documentation first
