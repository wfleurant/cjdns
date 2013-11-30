/* vim: set expandtab ts=4 sw=4: */
/*
 * You may redistribute this program and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
var Os = require('os');
var Fs = require('fs');
var Spawn = require('child_process').spawn;
var nThen = require('nthen');
var Extend = require('node.extend');
var Crypto = require('crypto');
var Semaphore = require('./Semaphore');

/*
 * Why hello dear packager,
 *
 * I suppose you have found this place as you are trying to figure out how to work this into your
 * build system. You're probably faced with a decision between getting node.js into your build and
 * "fixing" this build process so it doesn't need such a silly thing. A 500 line script is certainly
 * not unapproachable, right?
 * The reason why I am speaking to you now is because I care about you. I want you to be happy
 * and live a care free life, and because you are standing on the precipus of a cavern so dark and
 * deep that while you may well make it out alive, your personal pride and innocence almost
 * certainly will not. Imagine yourself after months of sleepless nights wallowing in the quicksand,
 * forever trying to slay the dragon which is always so close yet and so far away. Imagine the deep
 * hatred you will have for humanity, code, and the creator of this doomsday machine. I beg you to
 * turn back now while there is still hope. You need not die here, your life is important, and
 * whether you close this file now or not, in the end you will still end up including node.js in
 * your build.
 *
 * The Creator
 */

// Since many of the compile operations are short, the best performance seems
// to be when running 1.25x the number of jobs as cpu cores.
var WORKERS = Math.floor(Os.cpus().length * 1.25);

var error = function (message)
{
    try {
        throw new Error(message);
    } catch (e) {
        return e;
    }
};

var sema = Semaphore.create(WORKERS);
var compiler = function (args, callback, content) {
    sema.take(function (returnAfter) {
        var gcc = Spawn('gcc', args);
        var err = '';
        var out = '';
        gcc.stdout.on('data', function(dat) { out += dat.toString() ; });
        gcc.stderr.on('data', function(dat) { err += dat.toString() ; });
        gcc.on('close', returnAfter(function(ret) {
            callback(ret, out, err);
        }));
        if (content) {
            gcc.stdin.write(content, function (err) {
                if (err) { throw err; }
                gcc.stdin.end();
            });
        }
    });
};

var cc = function (args, callback, content) {
    compiler(args, function (ret, out, err) {
        if (ret) {
            callback(error("gcc " + args.join(' ') + "\n\n" + err));
        }
        if (err !== '') {
            debug(err);
        }
        callback(undefined, out);
    }, content);
};

// You Were Warned
var execJs = function (js, state, file, fileName, callback) {
    var res;
    var x;
    var err;
    // # 74 "./wire/Message.h"
    js = js.replace(/\n#.*\n/g, '');
    var to = setTimeout(function () {
        throw new Error("Inline JS did not return after 10 seconds [" + js + "]");
    }, 10000);
    nThen(function (waitFor) {
        try {
            var func = new Function('file','state','require','fileName',js);
            func.async = function () {
                return waitFor(function (result) {
                    res = result;
                });
            };
            x = func.call(func,file,state,require,fileName);
        } catch (e) {
            err = e;
            err.message += "\nContent: [" + js + "]";
            clearTimeout(to);
            process.nextTick(function() { callback(res); });
        }
    }).nThen(function (waitFor) {
        if (err) { return; }
        res = res || x || '';
        clearTimeout(to);
        process.nextTick(function() { callback(undefined, res); });
    });
};

var debug = console.log;

var preprocess = function (content, state, fileObj, fileName, callback) {
    var elems;
    nThen(function (waitFor) {
        elems = content.split('<?js');
        elems.forEach(function (elem, i) {
            if (!i) { return; }
            var capture = elem.substring(0,elem.indexOf('?>'));
            var remainder = elem.substring(capture.length+2);
            execJs(capture, state, fileObj, fileName, waitFor(function (err, ret) {
                if (err) {
                    callback(err);
                    callback = function() {};
                    return;
                }
                //debug('[' + capture + '] --> [' + ret + '] [' + remainder.substring(0,100) + ']');
                elems[i] = ret + remainder;
                //if (elems[i].indexOf('?>') !== -1) { throw new Error(); }
            }));
        });
    }).nThen(function (waitFor) {
        callback(undefined, elems.join(''));
    });
};

var getFile = function ()
{
    return {
        includes: [],
        links: [],
        cflags: [],
        oldmtime: 0
    };
};

var getObjectFile = function (cFile) {
    return cFile.replace(/[^a-zA-Z0-9_-]/g, '_')+'.o'
};

var currentlyCompiling = {};
var compileFile = function (fileName, state, tempDir, callback)
{
    if (typeof(state.files[fileName]) !== 'undefined') {
        callback();
        return;
    }
    if (typeof(currentlyCompiling[fileName]) !== 'undefined') {
        currentlyCompiling[fileName].push(callback);
        return;
    } else {
        currentlyCompiling[fileName] = [];
    }
    currentlyCompiling[fileName].push(callback);

    //debug('\033[2;32mCompiling ' + fileName + '\033[0m');
    var preprocessed = tempDir + '/' + getObjectFile(fileName) + '.i';
    var outFile = state.buildDir+'/'+getObjectFile(fileName);
    var fileContent;
    var fileObj = getFile();
    nThen(function (waitFor) {
        (function() {
            //debug("CPP -MM");
            var flags = ['-E', '-MM'];
            flags.push.apply(flags, state.cflags);
            flags.push.apply(flags, state['cflags'+fileName]);
            flags.push(fileName);
            cc(flags, waitFor(function (err, output) {
                if (err) { throw err; }
                // replace the escapes and newlines
                output = output.replace(/ \\|\n/g, '').split(' ');
                // first 2 entries are crap
                output.splice(0,2);
                fileObj.includes = output;
            }));
        })();

        (function() {
            //debug("CPP");
            var flags = ['-E'];
            flags.push.apply(flags, state.cflags);
            flags.push.apply(flags, state['cflags'+fileName]);
            flags.push(fileName);
            cc(flags, waitFor(function (err, output) {
                if (err) { throw err; }
                fileContent = output;
            }));
        })();

    }).nThen(function (waitFor) {

        //debug("Preprocess");
        preprocess(fileContent, state, fileObj, fileName, waitFor(function (err, output) {
            if (err) { throw err; }
            if (state.useTempFiles) {
                Fs.writeFile(preprocessed, output, waitFor(function (err) {
                    if (err) { throw err; }
                }));
                // important, this will prevent the file from also being piped to gcc.
                fileContent = undefined;
            } else {
                fileContent = output;
            }
        }));

        Fs.exists(outFile, waitFor(function (exists) {
            if (exists) {
                Fs.unlink(outFile, waitFor(function (err) {
                    if (err) { throw err; }
                }));
            }
        }));

    }).nThen(function (waitFor) {

        //debug("CC");
        var flags = ['-c','-x','cpp-output','-o',outFile];
        flags.push.apply(flags, state.cflags);
        flags.push.apply(flags, state['cflags'+fileName]);
        if (state.useTempFiles) {
            flags.push(preprocessed);
        } else {
            flags.push('-');
        }
        cc(flags, waitFor(function (err) {
            if (err) { throw err; }
            fileObj.obj = outFile;
        }), fileContent);

    }).nThen(function (waitFor) {
        debug('\033[2;32mBuilding C object ' + fileName + ' complete\033[0m');
        state.files[fileName] = fileObj;
        var callbacks = currentlyCompiling[fileName];
        delete currentlyCompiling[fileName];
        callbacks.forEach(function (cb) { cb(); });
    });
};

/**
 * @param files state.files
 * @param mtimes a mapping of files to times for files for which the times are known
 * @param callback when done.
 */
var getMTimes = function (files, mtimes, callback)
{
    nThen(function (waitFor) {
        Object.keys(files).forEach(function (fileName) {
            mtimes[fileName] = mtimes[fileName] || 0;
            files[fileName].includes.forEach(function (incl) {
                mtimes[incl] = mtimes[incl] || 0;
            });
        });
        Object.keys(mtimes).forEach(function (fileName) {
            if (mtimes[fileName] !== 0) { return; }
            Fs.stat(fileName, waitFor(function (err, stat) {
                if (err) {
                    waitFor.abort();
                    callback(err);
                    return;
                }
                mtimes[fileName] = stat.mtime.getTime();
            }));
        });
    }).nThen(function (waitFor) {
        callback(undefined, mtimes);
    });
};

var removeFile = function (state, fileName, callback)
{
    debug("remove " + fileName);
    nThen(function (waitFor) {
        // And every file which includes it
        Object.keys(state.files).forEach(function (file) {
            // recursion could remove it
            if (typeof(state.files[file]) === 'undefined') { return; }
            if (state.files[file].includes.indexOf(fileName) !== -1) {
                removeFile(state, file, waitFor());
            }
        });
        // we'll set the oldmtime on the file to 0 since it's getting rebuilt.
        state.oldmtimes[fileName] = 0;
        var f = state.files[fileName];
        if (typeof(f) === 'undefined') { return; }
        delete state.files[fileName];
        if (typeof(f.obj) === 'string') {
            Fs.unlink(f.obj, waitFor(function (err) {
                if (err && err.code !== 'ENOENT') { throw err; }
            }));
        }
    }).nThen(function (waitFor) {
        callback();
    });
};

var recursiveCompile = function (fileName, state, tempDir, callback)
{
    // Recursive compilation
    var doCycle = function (toCompile, parentStack, callback) {
        if (toCompile.length === 0) { callback(); return; }
        nThen(function(waitFor) {
            for (var file = toCompile.pop(); file; file = toCompile.pop()) {
                (function(file) {
                    var stack = [];
                    stack.push.apply(stack, parentStack);
                    //debug("compiling " + file);
                    stack.push(file);
                    if (stack.indexOf(file) !== stack.length-1) {
                        throw new Error("Dependency loops are bad and you should feel bad\n" + 
                                        "Dependency stack:\n" + stack.reverse().join('\n'));
                    }
                    compileFile(file, state, tempDir, waitFor(function () {
                        var toCompile = [];
                        state.files[file].links.forEach(function(link) {
                            if (link === file) { return; }
                            toCompile.push(link);
                        });
                        doCycle(toCompile, stack, waitFor(function () {
                            if (stack[stack.length-1] !== file) { throw new Error(); }
                            stack.pop();
                        }));
                    }));
                })(file);
            }
        }).nThen(function (waitFor) {
            callback();
        });
    };
    doCycle([fileName], [], callback);
};

var getLinkOrder = function (fileName, files) {
    var completeFiles = [];
    var getFile = function (name) {
        var f = files[name];
        //debug('Resolving links for ' + name + ' ' + f);
        for (var i = 0; i < f.links.length; i++) {
            if (f.links[i] === name) { continue; }
            if (completeFiles.indexOf(f.links[i]) > -1) { continue; }
            getFile(f.links[i]);
        }
        completeFiles.push(name);
    };
    getFile(fileName);
    return completeFiles;
};

var needsToLink = function (fileName, state) {
    if (typeof(state.oldmtimes[fileName]) !== 'number') {
        return true;
    }
    if (state.oldmtimes[fileName] !== state.mtimes[fileName]) {
        return true;
    }
    var links = state.files[fileName].links;
    for (var i = 0; i < links.length; i++) {
        if (links[i] !== fileName && needsToLink(links[i], state)) {
            return true;
        }
    }
    return false;
};

var makeTime = function () {
    return function () {
        var oldTime = this.time || 0;
        var newTime = this.time = new Date().getTime();
        return newTime - oldTime;
    };
};

var compile = function (file, outputFile, state, callback) {

    var tempDir;
    if (!needsToLink(file, state)) {
        callback();
        return;
    }

    nThen(function(waitFor) {

        if (!state.useTempFiles) { return; }
        tempDir = state.tempDir+'/jsmake-' + Crypto.pseudoRandomBytes(10).toString('hex');
        Fs.mkdir(tempDir, waitFor(function (err) {
            if (err) { throw err; }
        }));

    }).nThen(function(waitFor) {

        recursiveCompile(file, state, tempDir, waitFor());

    }).nThen(function(waitFor) {

        var linkOrder = getLinkOrder(file, state.files);
        for (var i = 0; i < linkOrder.length; i++) {
            linkOrder[i] = state.buildDir + '/' + getObjectFile(linkOrder[i]);
        }
        var ldArgs = [];
        ldArgs.push.apply(ldArgs, state.ldflags);
        ldArgs.push.apply(ldArgs, ['-o', outputFile]);
        ldArgs.push.apply(ldArgs, linkOrder);
        ldArgs.push.apply(ldArgs, state.libs);
        debug('\033[1;31mLinking C executable ' + outputFile + '\033[0m');

        cc(ldArgs, waitFor(function (err, ret) {
            if (err) { throw err; }
        }));

    }).nThen(function(waitFor) {

        if (!state.useTempFiles) { return; }
        Fs.readdir(tempDir, waitFor(function(err, files) {
            if (err) { throw err; }
            files.forEach(function(file) {
                Fs.unlink(tempDir + '/' + file, waitFor(function(err) {
                    if (err) { throw err; }
                }));
            });
        }));

    }).nThen(function(waitFor) {

        if (!state.useTempFiles) { return; }
        Fs.rmdir(tempDir, waitFor(function(err) {
            if (err) { throw err; }
        }));

    }).nThen(function(waitFor) {

        if (callback) { callback(); }

    });
};

var getStatePrototype = function () {
    return {
        includeDirs: ['.'],
        files: {},
        mtimes: {},

        cflags: [],
        ldflags: [],
        libs: [],

        // Using temp files instead of pipes shaves about 400ms off a clean build.
        // TODO: Understand why our use of pipes is not good.
        tempDir: '/tmp',
        useTempFiles: true,

        systemName: 'Linux'
    };
};

module.exports.configure = function (params, configure) {

    // Track time taken for various steps
    var time = makeTime();
    time();

    if (typeof(params.buildDir) !== 'string') {
        throw new Error("buildDir not specified");
    }

    var rebuildIfChangesHash = '';
    if (typeof(params.rebuildIfChanges) !== 'undefined') {
        rebuildIfChangesHash =
            Crypto.createHash('sha256').update(params.rebuildIfChanges).digest('hex');
    }

    var state;
    var buildStage;

    nThen(function(waitFor) {
        // make the build directory
        Fs.exists(params.buildDir, waitFor(function (exists) {
            if (exists) { return; }
            Fs.mkdir(params.buildDir, waitFor(function (err) {
                if (err) { throw err; }
            }));
        }));

    }).nThen(function(waitFor) {

        // read out the state if it exists
        Fs.exists(params.buildDir + '/state.json', waitFor(function (exists) {
            if (!exists) { return; }
            Fs.readFile(params.buildDir + '/state.json', waitFor(function (err, ret) {
                if (err) { throw err; }
                var storedState = JSON.parse(ret);
                if (storedState.rebuildIfChangesHash === rebuildIfChangesHash) {
                    state = storedState;
                } else {
                    debug("rebuildIfChanges changed, rebuilding");
                }
            }));
        }));

    }).nThen(function(waitFor) {

        debug("Initialize " + time() + "ms");

        // Do the configuration step
        if (state) { return; }
        state = getStatePrototype();
        configure({
            config: state,
            compiler: compiler
        }, waitFor);

    }).nThen(function(waitFor) {

        state.buildDir = params.buildDir;
        for (var i = 0; i < state.includeDirs.length; i++) {
            state.cflags.push('-I');
            state.cflags.push(state.includeDirs[i]);
        }
        

        debug("Configure " + time() + "ms");

    }).nThen(function(waitFor) {

        state.rebuildIfChangesHash = rebuildIfChangesHash;
        state.oldmtimes = state.mtimes;
        state.mtimes = {};

        Object.keys(state.oldmtimes).forEach(function (fileName) {
            Fs.stat(fileName, waitFor(function (err, stat) {
                if (err) { throw err; }
                state.mtimes[fileName] = stat.mtime.getTime();
                if (state.oldmtimes[fileName] !== stat.mtime.getTime()) {
                    debug(fileName + ' is out of date, rebuilding');
                    removeFile(state, fileName, waitFor());
                }
            }));
        });

    }).nThen(function(waitFor) {

        debug("Scan for out of date files " + time() + "ms");

    }).nThen(function(waitFor) {

        buildStage({
            compile: function (cFile, outputFile) {
                compile(cFile, outputFile, state, waitFor());
            }
        }, waitFor);

    }).nThen(function(waitFor) {

        debug("Compile " + time() + "ms");

    }).nThen(function(waitFor) {

        getMTimes(state.files, state.mtimes, waitFor(function (err, mtimes) {
            if (err) { throw err; }
            state.mtimes = mtimes;
        }));

    }).nThen(function(waitFor) {

        // save state
        debug("Saving state");
        var stateJson = JSON.stringify(state, null, '  ');
        Fs.writeFile(state.buildDir+'/state.json', stateJson, waitFor(function(err) {
            if (err) { throw err; }
        }));

    });

    return {
        build: function (build) {
            buildStage = build;
        }
    };
};
