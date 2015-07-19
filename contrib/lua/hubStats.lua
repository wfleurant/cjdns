#!/usr/bin/env lua

local rocks = require "luarocks.loader"
cjdns  = require "cjdns/init"
dkjson = require "dkjson"

confpath = "../../cjdroute.conf"
cjdns    = require "cjdns/init"
conf     = cjdns.ConfigFile.new(confpath)
admin    = conf:makeInterface()
publictoip6 = "../../publictoip6 "

--[[ function deprint, usage: visually debug string (f) and value (v) ]]--
function deprint(f,v)
	if not f then print(' -> ')
	elseif (v and type(v) == 'string') then print(f .. ' -> ' .. v)
	elseif (v and type(v) == 'table') then
		print(f .. ' -> table \n{')
		for k,v in pairs(v) do
			print('',k,v)
		end; print('}')
	else print(f .. ' ->  ')
	end
end

function publictoip6(publicKey)
	local process = io.popen("../../publictoip6 " .. publicKey, "r")
	local ipv6    = process:read()
	process:close()
	return ipv6
end

-- InterfaceController_peerStats
function peer_stats()
	--[[
		publicKey			hhj3hk5npmxvq52gd2kr7qst3vwtym4jv2vjdtp1flkj2u7s86m0.k
		last				1433626160685
		switchLabel			0000.0000.0000.0017
		receivedOutOfRange	0
		bytesOut			29188391
		ipv6				fcfe:f4ce:609f:434b:aa44:6ea0:ebc2:2d89
		state				ESTABLISHED
		addr				v16.0000.0000.0000.0017.hhj3hk5npmxvq52gd2kr7qst3vwtym4jv2vjdtp1flkj2u7s86m0.k
		isIncoming			1
		version				16
		lostPackets			9
		bytesIn				25667257
		user				Local Peers
		duplicates			0
	]]--
	-- require("cjdns/uci")
	-- admin = cjdns.uci.makeInterface()
	admin = conf:makeInterface()

	local page = 0
	local peers = {}

	while page do
		local response, err = admin:auth({
			q = "InterfaceController_peerStats",
			page = page
		})

		for i,peer in pairs(response.peers) do
			peer.ipv6 = publictoip6(peer.publicKey)
			peers[#peers + 1] = peer
		end

		if response.more then
			page = page + 1
		else
			page = nil
		end
	end

	return(peers)

end


-- NodeStore_dumpTable
function dump_table()
	--[[
		addr	v16.0000.0003.a7b3.2ba7.6bpfrstnh5hkpvr0xby36ts9y044hrpr76nwtmbsrcdj54uhvy00.k
		time	28484
		link	12083911
		version	16
		path	0000.0003.a7b3.2ba7
		ip		fc87:1a28:d22b:208a:fc5f:056f:6e93:fcc4
	]]--
	--require("cjdns/uci")
	-- admin = cjdns.uci.makeInterface()
	admin = conf:makeInterface()
	local page = 0
	local peers = {}

	while page do
		local response, err = admin:auth({
			q = "NodeStore_dumpTable",
			page = page
		})

		for i,peer in pairs(response.routingTable) do
			peers[#peers + 1] = peer
		end

		if response.more then
			page = page + 1
		else
			page = nil
		end
	end
	return(peers)
end



------------------------
-- Adding New Service --
------------------------
function hype_add_form_submit(table)

	local method = "POST"
	local url = "http://api.hyperboria.net:8000/api/v1/node/update.json"
	local lesock = httpsock(method, url, table)
	deprint('treeStats', table.treeStats)
	deprint('dumpTable', table.dumpTable)
	deprint('sessStats', table.sessStats)
	deprint('peerstats', table.peerstats)
	return(lesock)

end

-------------------
-- POST/GET HTTP --
-------------------
function httpsock(method, url, reqbody) -- Diego Nehab

	local http = require("socket.http")
	local ltn12 = require("ltn12")
	local json = require("dkjson")

	-- Request
	if type(reqbody) == 'table' then
		reqbody = json.encode(reqbody)
	elseif type(reqbody) == 'string' then
		reqbody = json.encode(reqbody)
	end

	-- Response
	local respbody = {} -- for the response body

	-- Start HTTP socket to post/get reqbody
	local result, respcode, respheaders, respstatus, respsrc = http.request {
		url = url,
		sink = ltn12.sink.table(respbody),
		method = method,
		headers = {
		    ["content-type"] = "application/x-www-form-urlencoded",
		    ["content-length"] = tostring(#reqbody)
		},
		source = ltn12.source.string(reqbody)
	}

	-- Extra
	if 'debug' == 'on' then
		deprint('result', result)
		deprint('respcode', respcode)
		deprint('respheaders', respheaders)
		deprint('respstatus', respstatus)
		deprint('respbody', respbody)
		deprint('reqbody', reqbody)
		deprint('contentLen', #reqbody)
		deprint('reqbodyJson', reqbody)
		deprint('respsrc', respsrc)
	end

	-- get body as string by concatenating table filled by sink

	return(respbody)
end



-- SessionManager_getHandles
function get_handles()
	--[[
		1	11921
		2	11920
		3	11919
		4	11918
		5	11917
	]]--
	-- require("cjdns/uci")
	-- admin = cjdns.uci.makeInterface()
	admn = conf:makeInterface()
	local page = 0
	local handles = {}

	while page do
		local response, err = admin:auth({
			q = "SessionManager_getHandles",
			page = page
		})

		for i,handle in pairs(response.handles) do
			handles[#handles + 1] = handle
		end

		if response.more then
			page = page + 1
		else
			page = nil
		end
	end

	return(handles)

end


-- SessionManager_sessionStats
function sess_stats()
	--[[
		publicKey			32dr89uhb358jxkfgjgc0rkjnkcpbjd287tqdh6cw2shunnl3jn0.k
		ip6					fcdc:71fe:e8c2:f3ba:ca93:80f4:3d97:2096
		sendHandle			74709
		receivedOutOfRange	0
		timeOfLastOut		1433624102670
		state				CryptoAuth_HANDSHAKE2
		addr				v16.0000.01b9.40b3.2ba7.32dr89uhb358jxkfgjgc0rkjnkcpbjd287tqdh6cw2shunnl3jn0.k
		version				16
		timeOfLastIn		1433624107006
		handle				12000
		deprecation			publicKey,version will soon be removed
		lostPackets			0
		duplicates			0
	]]--

	-- require("cjdns/uci")
	-- admin = cjdns.uci.makeInterface()
	admin = conf:makeInterface()
	local handles = get_handles()
	local ss = {}
	for i,handle in pairs(handles) do
		local response, err = admin:auth({
			q = "SessionManager_sessionStats",
			handle = handle,
		})
		ss[handle] = response
	end

	return(ss)

end



function tree_paths()
	-- NodeStore_dumpTable
	-- NodeStore_dumpTable.routingTable
	return { tree_paths = 'tree_paths()' }
end


----------
-- Main --
----------


local hubStats = {}
hubStats.dumpTable = dump_table()
hubStats.peerstats = peer_stats()
hubStats.sessStats = sess_stats()
hubStats.treeStats = tree_paths()

-- {"result":"acceptable"}
print(hype_add_form_submit(hubStats)[1])
