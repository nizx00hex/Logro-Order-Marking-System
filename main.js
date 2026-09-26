const { app, BrowserWindow, shell } = require('electron');
const path = require('path');
const { spawn } = require('child_process');
const http = require('http');

let phpProcess = null;
let mainWindow = null;
const PORT = 8421;

// Determine where web files live (handles dev vs packaged .asar.unpacked)
function getAppDirectory() {
    if (!app.isPackaged) {
        return __dirname;
    }
    // When packaged, files are unpacked here:
    return path.join(process.resourcesPath, 'app.asar.unpacked');
}

function startPhpServer() {
    const appDir = getAppDirectory();
    const userDataDir = app.getPath('userData');

    let phpPath;
    if (process.platform === 'win32') {
        phpPath = app.isPackaged
            ? path.join(process.resourcesPath, 'bin', 'php', 'php.exe')
            : path.join(__dirname, 'bin', 'php', 'php.exe');
    } else {
        phpPath = 'php';
    }

    // Set custom environment variables so PHP knows where SQLite database belongs
    const env = Object.assign({}, process.env, {
        APP_DATA_DIR: userDataDir
    });

    console.log(`Starting PHP from: ${phpPath}`);
    console.log(`Document Root: ${appDir}`);

    phpProcess = spawn(phpPath, ['-S', `127.0.0.1:${PORT}`, '-t', appDir], { env });

    phpProcess.stdout?.on('data', (data) => console.log(`PHP: ${data}`));
    phpProcess.stderr?.on('data', (data) => console.log(`PHP Stderr: ${data}`));
    phpProcess.on('error', (err) => console.error('Failed to spawn PHP:', err));
}

// Check if PHP server is responding before showing the window
function waitForServer(callback, retries = 30) {
    if (retries <= 0) {
        console.error('PHP server did not start in time.');
        return callback(new Error('Server timeout'));
    }

    http.get(`http://127.0.0.1:${PORT}/index.html`, (res) => {
        if (res.statusCode === 200) {
            callback(null);
        } else {
            setTimeout(() => waitForServer(callback, retries - 1), 200);
        }
    }).on('error', () => {
        setTimeout(() => waitForServer(callback, retries - 1), 200);
    });
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1200,
        height: 800,
        minWidth: 800,
        minHeight: 600,
        title: "Delivery Order System",
        autoHideMenuBar: true,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        }
    });

    // Handle external links (like EliteFort watermark)
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        if (url.startsWith('http:') || url.startsWith('https:')) {
            shell.openExternal(url);
            return { action: 'deny' };
        }
        return { action: 'allow' };
    });

    mainWindow.loadURL(`http://127.0.0.1:${PORT}/index.html`);

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

app.whenReady().then(() => {
    startPhpServer();

    // Wait until the PHP web server is actually listening
    waitForServer((err) => {
        if (!err) {
            createWindow();
        } else {
            console.error("Could not reach PHP server.");
        }
    });

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});

app.on('will-quit', () => {
    if (phpProcess) {
        phpProcess.kill();
    }
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});