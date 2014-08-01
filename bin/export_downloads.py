#!/usr/bin/python3
"""
sudo apt-get install python3-mysql.connector python3-pip
sudo pip3 install networkx
sudo pip3 install numpy

sp x loc all csak header ak?r prot, ak?r int
"""

from contextlib import closing
import argparse
import logging
import os
import io
import configparser
# python3-mysql.connector: http://dev.mysql.com/doc/connector-python/en/index.html
import mysql.connector
import networkx as nx
import gzip
import pickle
import csv

class ComppiInterface(object):
	"""
	Class to get and export data from the ComPPI database.

	attrib cfg_file: str, the parameters.ini file of Symphony to share the DB settings
	"""

	cache_enabled 			= True
	comppi_global_graph_f 	= 'global_comppi_graph.gzpickle'
	cfg_file				= os.path.join('..', 'app', 'config', 'parameters.ini')
	log_file				= os.path.join('..', 'web', 'download', 'export_networks.log')
	log_mode				= 'a' # a: append, w: overwrite
	output_dir 				= os.path.join('..', 'web', 'download')
	db_conn					= None
	cursor					= None
	db_name 				= ''
	db_host 				= ''
	db_user 				= ''
	db_pwd  				= ''
	specii = {
		0 : '9606', # H. sapiens
		1 : '7227', # D. melanogaster
		2 : '6239', # C. elegans
		3 : '4932' # S. cerevisiae
	}
	specii_opts = {
		'hsapiens': 0, # H. sapiens
		'dmelanogaster': 1, # D. melanogaster
		'celegans': 2, # C. elegans
		'scerevisiae': 3, # S. cerevisiae
		'all': 4
	}
	locs = ['cytoplasm', 'extracellular', 'mitochondrion', 'secretory-pathway', 'nucleus', 'membrane']
	loc_opts = ['cytoplasm', 'extracellular', 'mitochondrion', 'secretory-pathway', 'nucleus', 'membrane', 'all']
	exp_system_types = {
		0: 'Unknown',
		1: 'Experimental',
		2: 'Predicted'
	}


	def __init__(self):
		logging.basicConfig(
				filename = self.log_file,
				filemode = self.log_mode, # a for append, w for overwrite
				format = '%(asctime)s - %(levelname)s - %(message)s',
				level = logging.DEBUG)

		self.logging = logging
		self.logging.info("X----- STARTED ----------")

		cfg = configparser.ConfigParser()
		cfg.read(self.cfg_file)

		self.logging.info("Config loaded")

		self.db_name	= cfg['parameters']['database_name']
		self.db_host 	= cfg['parameters']['database_host']
		self.db_user 	= cfg['parameters']['database_user']
		self.db_pwd  	= cfg['parameters']['database_password']


	def connect(self):
		if self.db_conn is None:
			self.logging.info("Connecting to database...")
			# throws mysql.connector.errors.InterfaceError if no server
			self.db_conn = mysql.connector.connect(
				host=self.db_host,
				user=self.db_user,
				passwd=self.db_pwd,
				database=self.db_name,
				# buffered=True is needed if 2 or more cursors are used, otherwise
				# python-mysql.connector throws "raise errors.InternalError("Unread result found.")" error
				# see also http://planet.mysql.com/entry/?id=26522
				buffered=True
			)
			self.logging.info("Database connection established")

		# returning a new cursor object every time prevents overwrite of cursor buffer
		return self.db_conn.cursor(buffered=True)


	def getGlobalEdgeTable(self):
		self.logging.debug("getGlobalEdgeTable() started")

		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT
					i.id, i.actorAId, i.actorBId, i.sourceDb, i.pubmedId,
					cs.score,
					itst.systemTypeId
				FROM Interaction i
				LEFT JOIN ConfidenceScore cs ON i.id=cs.interactionId
				LEFT JOIN InteractionToSystemType itst ON i.id=itst.interactionId
			"""
			self.logging.debug(sql)
			cur.execute(sql)

			et_buffer = {} # buffer for existing edges
			for iid, actor_a, actor_b, i_src_db, i_pubmed, i_score, i_exp_sys_t in cur:
				# each row: (node A comppi ID, node B comppi ID, {dict of edge properties})
				if i_score is None:
					i_score = 0.0 # ambivalent, true 0.0 and None both map to 0.0
				else:
					i_score = float(i_score)
				
				if i_pubmed==0:
					i_pubmed = 'N/A'

				# add edge if it's new
				if (actor_a, actor_b) not in et_buffer:
					#try:
					et_buffer[(actor_a, actor_b)] = {
						'weight': i_score, # weights are always the same for the same interactors
						'source_dbs': [i_src_db],
						'pubmed_ids': [i_pubmed],
						'edge_ids': [iid],
						'int_exp_system_type_ids': [i_exp_sys_t]
					}
				
				# aggregate data per edge if the edge already exists
				else:
					d = et_buffer[(actor_a, actor_b)]
					
					#d['weight'].append(i_score) - add weights only once
					d['source_dbs'].append(i_src_db)
					d['pubmed_ids'].append(i_pubmed)
					d['edge_ids'].append(iid)
					d['int_exp_system_type_ids'].append(i_exp_sys_t)
			
			# convert the much faster et_buffer to the required edge table format
			et = [(actors[0], actors[1], attribs) for actors, attribs in et_buffer.items()]

			self.logging.debug("getGlobalEdgeTable() returns with {} rows".format(len(et)))
			return et


	def buildGraphFromEdgetable(self, edgetable, nw_name = 'directed_weighted_ppi', only_main_component = False, strict_check = False):
		""" Build a weighted, directed graph from an edgetable.

			Each line of the edge table must contain node1, node1, attribute dict, where the attribute dict must contain at least one 'weight' key with a scalar value. Arbitrary number of other edge attributes are allowed, these all will be included in the graph.

			There may be multiple components in the network (not connected parts). If the 'only_main_component' flag is set to True and there are multiple components, the output graph will be the largest connected component.
			The Networkx connected_components_* methods don't support directed graphs, so we create first an undirected graph, check if there are multiple components, take the largest one and create a directed graph from the largest component.

			param edgetable: list of tuples, each tuple contains two node IDs and a dictionary with the edge weight and optional other edge properties.
			Each tuple have to contain the edge weight, e.g.
				edgetable = [(node1, node2, {"weight": 0.8})]
			and may contain additional edge properties:
				edgetable = [(node1, node2, {"weight": 0.8, "some_property": "A"})]
			The edgetable is the direct input for the networkx add_edges_from() method.
			param nw_name: the filesystem-friendly name of the network (mostly used for caching purposes)
			param only_main_component: bool, optional. If true, the network will be the main connected component (the largest connected subgraph) of the network from the edgetable.
			returns: networkx weighted, directed graph object
		"""
		self.logging.debug("buildGraphFromEdgetable() started")

		# primitive tests to see if the edgetable is populated...
		if len(edgetable)==0:
			self.logging.critical("buildGraphFromEdgetable(): empty edgetable!")
			raise Exception('Empty edgetable!')

		# ... strict check of the edgetable structure
		# required minimum for each row: (node1, node2, {"weight": 0.8})
		if strict_check:
			for row in edgetable:
				if len(row)!=3 or not isinstance(row[2], dict) or row[2].get("weight") is None:
					self.logging.critical("buildGlobalDiGraph(): missing edge weight!")
					raise Exception('Missing edge weight!')

		dg = nx.DiGraph()
		dg.graph['name'] = nw_name

		if only_main_component:
			# main component is only supported on undirected graphs
			ug = nx.Graph()
			ug.add_edges_from(edgetable) # undirected global graph
			if nx.number_connected_components(ug)>1:
				lcug = nx.connected_component_subgraphs(ug)[0]
			else:
				lcug = ug
			del ug

			# add the main connected component as the graph to the directed graph
			dg.add_edges_from(lcug.edges(data=True))
		else:
			dg.add_edges_from(edgetable)

		self.logging.info("buildGraphFromEdgetable(): de novo constructed, nodes: {}, edges: {}".format(dg.number_of_nodes(), dg.number_of_edges()))
		return dg


	def buildGlobalComppi(self):
		"""
			Resources are freed up manually to save memory.
		"""
		self.logging.debug("buildGlobalComppi() started")
		if self.cache_enabled and os.path.isfile(self.comppi_global_graph_f):
			with gzip.open(self.comppi_global_graph_f, 'rb') as fp:
				self.logging.info("buildGlobalComppi(): global graph loaded from cache")
				return pickle.load(fp)
		else:
			# build the graph
			et			= self.getGlobalEdgeTable()
			graph		= self.buildGraphFromEdgetable(et)
			del et
			
			# there may be nodes without interactions - add these too
			all_nodes	= self.getAllProteinIds()
			graph		= self.addNodesToGraph(graph, all_nodes)
			del all_nodes

			# annotate the nodes
			prot_dtls	= self.getProteinDetails()
			prot_syns	= self.getProteinSynonyms()
			locs		= self.getLocalizations()

			for n, d in graph.nodes_iter(data=True):
				pd	= prot_dtls.get(n, {})
				ps	= prot_syns.get(n, {})
				lc	= locs.get(n, {})

				# these are the node properties
				d['name']			= pd.get('name')
				d['naming_conv']	= pd.get('naming_conv')
				d['taxonomy_id']	= pd.get('taxonomy_id')
				d['synonyms']		= ps.get('names')
				d['minor_locs']		= lc.get('minor_locs')
				d['major_locs']		= lc.get('major_locs')
				d['loc_scores']		= lc.get('loc_scores')
				d['loc_source_dbs']	= lc.get('source_dbs')
				d['loc_pubmed_ids']	= lc.get('pubmed_ids')
				d['loc_exp_sys']	= lc.get('loc_exp_sys_merged')

			del prot_dtls
			del prot_syns
			del locs

			# annotate the edges (see also self.getGlobalEdgeTable())
			exp_sys		= self.getExperimentalSystemTypes()

			for n1, n2, e_attr in graph.edges_iter(data=True):
				# experimental system type edge attribute
				e_attr['int_exp_sys'] = [] # create it
				
				est_ids = e_attr.get('int_exp_system_type_ids') # fill it if available
				if isinstance(est_ids, list):
					for est_id in est_ids:
						e_attr['int_exp_sys'].append(exp_sys.get(est_id))

			del exp_sys

			self.logging.debug("buildGlobalComppi(): global graph has been constructed de novo, number of nodes: {}, number of edges: {}".format(graph.number_of_nodes(), graph.number_of_edges()))

			with gzip.open(self.comppi_global_graph_f, 'wb') as fp:
				pickle.dump(graph, fp)
				self.logging.info("buildGlobalComppi(): global graph dumped to cache")

			return graph


	def filterGraph(self, input_graph, loc, species_id, in_place = True):
		""" Current filterGraph ALWAYS filters out nodes without localizations.
			Use the original graph for an unfiltered dataset.
		"""
		self.logging.debug("filterGraph() started, in_place: '{}', loc: '{}', species_id: '{}'".format(in_place, loc, species_id))

		# warning: graph copy can be terribly slow
		if in_place:
			graph = input_graph
		else:
			graph = input_graph.copy()

		# get protein IDs by loc, IDs by species, intersect, and get the graph containing only those nodes
		loc_node_ids = None
		spec_node_ids = None
		sp_keys = self.specii.keys()
		sp_id = int(species_id)
		
		if loc in self.loc_opts and sp_id in sp_keys:
			loc_node_ids = self.getNodeIdsByMajorLoc(loc)
			spec_node_ids = self.getNodeIdsBySpeciesId(species_id)
			node_ids = set.intersection(loc_node_ids, spec_node_ids)
			del loc_node_ids
			del spec_node_ids
			self.logging.debug("filterGraph(): graph filtered for loc '{}' and species '{}'".format(loc, sp_id))
		elif loc in self.loc_opts and sp_id not in sp_keys:
			node_ids = self.getNodeIdsByMajorLoc(loc)
			self.logging.debug("filterGraph(): graph filtered for loc '{}'".format(loc))
		elif loc not in self.loc_opts and species_id in self.specii.keys():
			node_ids = self.getNodeIdsBySpeciesId(species_id)
			self.logging.debug("filterGraph(): graph filtered for species '{}'".format(sp_id))
		else:
			self.logging.debug("filterGraph() returns with the original graph")
			return graph

		# in-place filtering to save memory
		graph.remove_nodes_from( [n for n in graph if n not in node_ids] ) # note the 'not in'

		self.logging.info("filterGraph() returns with a filtered graph: {} nodes, {} edges".format(graph.number_of_nodes(), graph.number_of_edges()))
		return graph


	def buildEgoGraph(self, graph, node_id, radius=1):
		self.logging.debug("buildEgoGraph() started")

		return nx.ego_graph(graph, node_id, radius=radius, undirected=True)


	def addNodesToGraph(self, graph, nodes):
		"""
			param nodes: any iterable
		"""
		self.logging.debug("addNodesToGraph() started")
		graph.add_nodes_from(nodes)
		return graph
	
	
	def getAllProteinIds(self):
		self.logging.debug("getAllProteinIds() started")
		nodes = []
		
		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT DISTINCT id FROM Protein
			"""
			self.logging.debug(sql)
			cur.execute(sql)
			
			for node_row in cur:
				nodes.append(node_row[0])
		
		self.logging.debug("getAllProteinIds() returns with {} proteins".format(len(nodes)))
		return nodes


	def getProteinDetails(self):
		self.logging.debug("getProteinDetails() started")

		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT
					id, proteinName, proteinNamingConvention, specieId
				FROM Protein
			"""
			self.logging.debug(sql)
			cur.execute(sql)

			d = {}
			for pid, name, naming_conv, species_id in cur:
				d[pid] = {
					'name': name,
					'naming_conv': naming_conv,
					'taxonomy_id': self.specii.get(species_id, -1)
				}

			self.logging.debug("getProteinDetails() returns with {} rows".format(len(d)))
			return d


	def getProteinSynonyms(self):
		self.logging.debug("getProteinSynonyms() started")

		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT
					proteinId,
					GROUP_CONCAT(name SEPARATOR '|') as names,
					GROUP_CONCAT(namingConvention SEPARATOR '|') as naming_convs
				FROM NameToProtein
				GROUP BY proteinId
			"""
			self.logging.debug(sql)
			cur.execute(sql)

			d = {}
			for pid, names, naming_convs in cur:
				d[pid] = {
					'names': names,
					'naming_convs': naming_convs
				}

			return d


	def getLocalizations(self):
		self.logging.debug("getLocalizations() started")

		cursor_ls = self.connect()
		loc_scores = {}
		with closing(cursor_ls) as cur_ls:
			sql_ls = """
				SELECT proteinId, majorLocName, score FROM LocalizationScore ls
			"""
			self.logging.debug(sql_ls)
			cur_ls.execute(sql_ls)
		
			for pid, major_loc, score in cur_ls:
				curr_mloc_sc = loc_scores.setdefault(pid, {})
				curr_mloc_sc[major_loc] = score

		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT
					ptl.proteinId as pid, ptl.sourceDb, ptl.pubmedId,
					lt.goCode, lt.majorLocName,
					pltst.systemTypeId AS exp_sys_id
				FROM ProtLocToSystemType pltst, ProteinToLocalization ptl
				LEFT JOIN Loctree lt ON ptl.localizationId=lt.id
				WHERE ptl.id=pltst.protLocId
			"""
			self.logging.debug(sql)
			cur.execute(sql)

			all_exp_sys = self.getExperimentalSystemTypes()
			d = {}
			# dict of localization data, keyed by protein ID
			for pid, source_db, pubmed, go_code, major_loc, exp_sys_id in cur:
				curr_p = d.setdefault(pid, {})

				curr_p.setdefault('source_dbs', [])
				curr_p['source_dbs'].append(source_db)

				curr_p.setdefault('pubmed_ids', [])
				curr_p['pubmed_ids'].append(pubmed)

				curr_p.setdefault('minor_locs', [])
				curr_p['minor_locs'].append(go_code)

				curr_p.setdefault('major_locs', [])
				if major_loc not in curr_p['major_locs']:
					curr_p['major_locs'].append(major_loc)

				# concatenated name + type, such as 'SVM decision tree (Predicted)'
				curr_p.setdefault('loc_exp_sys_merged', [])
				curr_p['loc_exp_sys_merged'].append(all_exp_sys.get(exp_sys_id))

				# record one major loc only once
				# example: loc_scores: {'cytoplasm': 0.9, 'nucleus': 0.5}
				curr_p.setdefault('loc_scores', {})
				curr_ls = loc_scores.get(pid, {})
				for curr_maj_loc, curr_score in curr_ls.items():
					curr_p['loc_scores'][curr_maj_loc] = curr_score

			del all_exp_sys

			self.logging.debug("getLocalizations() returns with {} protein ID and localization data".format(len(d)))
			return d


	def getExperimentalSystemTypes(self):
		""" Load all experimental system types.
			returns: dict keyed by system type IDs, values are strings

			Example:
			>>> st = self.getExperimentalSystemTypes()
			>>> st
			... {0: 'SVM decision tree (Predicted)'}

		"""
		self.logging.debug("getExperimentalSystemTypes() started")

		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT st.id, st.name, st.confidenceType FROM SystemType st
			"""
			self.logging.debug(sql)
			cur.execute(sql)

			d = {}
			for stid, exp_sys, conf_type in cur:
				est = self.exp_system_types.get(conf_type)
				if est is not None:
					d[stid] = exp_sys + '(' + est + ')'
				else:
					d[stid] = exp_sys

			self.logging.debug("getExperimentalSystemTypes() returns with {} system types".format(len(d)))
			return d


	def getNodeIdsByMajorLoc(self, locs):
		"""
			Get the distinct node IDs of the proteins belonging to one or more major cell compartments.

			param loc: string, the name of the major localization; see also self.loc_opts (self.loc does not contain 'all', while self.loc_opts does)
			returns: set, the unique node IDs
		"""
		self.logging.debug("getNodeIdsByMajorLoc() started, loc(s): '{}'".format(locs))

		# ugly parameter mapping thanks to the poor mysql.connector API
		if locs=='all':
			locs = "'" + "', '".join(self.locs) + "'"
		elif isinstance(locs, str):
			if locs not in self.locs:
				raise ValueError("getNodeIdsByMajorLoc(): Unknown major localization name: '{}'".format(locs))
			locs = "'" + locs + "'"
		elif isinstance(locs, (tuple, list)):
			for ll in locs:
				if ll not in self.locs:
					raise ValueError("getNodeIdsByMajorLoc(): Unknown major localization name: '{}' in '{}'".format(ll, locs))
			locs = "'" + "', '".join(locs) + "'"
		else:
			raise ValueError("getNodeIdsByMajorLoc(): Major loc(s) must be in a tuple or a valid string. Locs: '{}'".format(locs))

		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT ptl.proteinId
				FROM ProteinToLocalization ptl
				LEFT JOIN Loctree lt ON ptl.localizationId=lt.id
				WHERE
					lt.majorLocName IN({})
			""".format(locs)
			self.logging.debug("getNodeIdsByMajorLoc(): {}".format(sql))
			cur.execute(sql)
			
			n_ids = set([l[0] for l in cur])

			self.logging.debug("getNodeIdsByMajorLoc() returns with {} node IDs".format(len(n_ids)))
			return n_ids


	def getNodeIdsBySpeciesId(self, sp_id):
		"""
			Get the distinct node IDs of a given species.

			param sp_id: int, the ID of the species; see also self.specii
			returns: set, the unique node IDs
		"""
		self.logging.debug("getNodeIdsBySpeciesId() started, species ID: '{}'".format(sp_id))

		if sp_id not in self.specii:
			raise ValueError("getNodeIdsBySpeciesId(): Unknown species ID: '{}'".format(sp_id))

		cursor = self.connect()
		with closing(cursor) as cur:
			sql = """
				SELECT id FROM Protein WHERE specieId = %s
			"""
			cur.execute(sql, (sp_id,))

			n_ids = set([r[0] for r in cur])
			self.logging.debug("getNodeIdsBySpeciesId() returns with {} node IDs".format(len(n_ids)))

			return n_ids
		
	
	def exportCompartmentToCsv(self, graph, filename, node_columns, edge_columns, header = tuple(), flatten = True, skip_none_lines = True):

		self.logging.info("""exportCompartmentToCsv() started,
			filename: {}
			header: {}
			node_columns: {}
			edge_columns: {}
			flatten: {}
			skip_none_lines: {}
		""".format(filename, header, node_columns, edge_columns, flatten, skip_none_lines))

		if header and (len(header) != (len(node_columns)*2 + len(edge_columns))):
			raise ValueError("""exportNetworkToCsv(): length of header is not the same as 2*node columns + edge columns!
				node colums: {}
				edge columns: {}
				header: {}
				""".format(node_columns, edge_columns, header))

		row_count = 0
		empty_row_count = 0
		with gzip.open(filename, 'w') as fp:
			csvw = csv.writer(
				io.TextIOWrapper(fp, newline="", write_through=True), # text into binary file
				delimiter="\t",
				quoting=csv.QUOTE_MINIMAL
			)

			# header
			if header:
				csvw.writerow([attr for attr in header])
			else:
				h1 = []
				h2 = []
				h3 = []
				for attr in node_columns:
					h1.append("node1:{}".format(attr))
					h2.append("node2:{}".format(attr))
				for attr in edge_columns:
					h3.append(attr)
				csvw.writerow(h1 + h2 + h3)

			# export data
			for n1, n2, e in graph.edges_iter(data=True):
				n1_d = graph.node[n1]
				n2_d = graph.node[n2]
				#mlocsc_n1 = n1_d.get('loc_scores', {})
				#mlocsc_n2 = n2_d.get('loc_scores', {})
				#majorlocs_n1 = set(mlocsc_n1.keys())
				#majorlocs_n2 = set(mlocsc_n2.keys())
				
				common_mlocs = set.intersection(set(n1_d['major_locs']), set(n2_d['major_locs']))
				
				if common_mlocs and ('N/A' not in common_mlocs):
					#print("{}->{}, mlocs1: {}, mlocs2: {}, common: {}".format(n1, n2, majorlocs_n1, majorlocs_n2, common_mlocs))
					curr_node1_cells = self._aggregateCsvCells(graph.node[n1], node_columns, flatten, skip_none_lines)
					curr_node2_cells = self._aggregateCsvCells(graph.node[n2], node_columns, flatten, skip_none_lines)
					curr_edge_cells	 = self._aggregateCsvCells(e, edge_columns, flatten, skip_none_lines)
	
					if curr_node1_cells and curr_node2_cells and curr_edge_cells:
						csvw.writerow(curr_node1_cells + curr_node2_cells + curr_edge_cells)
						row_count += 1
					else:
						empty_row_count += 1

		self.logging.debug("exportNetworkToCsv() returns with {} rows + header, {} empty rows have been thrown away".format(row_count, empty_row_count))


	def exportNetworkToCsv(self, graph, filename, node_columns, edge_columns, header = tuple(), flatten = True, skip_none_lines = True):
		""" Write the network nodes and edges with all their attributes to a tab-delimited CSV file.

			The method creates a merged, all-in type CSV file. Each row refers to an edge, and contains all the node properties for both nodes and all the edge properties:
				node1, node2, node1 attr1, node1 attr2, ... node2 attr1, node2 attr2, ..., edge attr1, edge attr2, ...
			If you want to have network edgetable in CSV format, use the networkx built-in graph writing methods (e.g. networkx.write_edgelist())

			If an attribute in 'node_columns'/'edge_columns' is not found for a node/edge, a KeyError is raised.

			param graph: networkx.Graph or networkx.DiGraph object, the graph to export
			param filename: the filename of the CSV file
			param node_columns: tuple of strings, must contain the exact node attribute names (data dict keys for a node) which are required to be exported. The order of the attribute names determines the order of columns.
			param edge_columns: tuple of strings, must contain the exact edge attribute names (data dict keys for an edge) which are required to be exported. The order of the attribute names determines the order of columns.
		"""
		self.logging.info("""exportNetworkToCsv() started,
			filename: {}
			header: {}
			node_columns: {}
			edge_columns: {}
			flatten: {}
			skip_none_lines: {}
		""".format(filename, header, node_columns, edge_columns, flatten, skip_none_lines))

		if header and (len(header) != (len(node_columns)*2 + len(edge_columns))):
			raise ValueError("""exportNetworkToCsv(): length of header is not the same as 2*node columns + edge columns!
				node colums: {}
				edge columns: {}
				header: {}
				""".format(node_columns, edge_columns, header))

		row_count = 0
		empty_row_count = 0
		with gzip.open(filename, 'w') as fp:
			csvw = csv.writer(
				io.TextIOWrapper(fp, newline="", write_through=True), # text into binary file
				delimiter="\t",
				quoting=csv.QUOTE_MINIMAL
			)

			# header
			if header:
				csvw.writerow([attr for attr in header])
			else:
				h1 = []
				h2 = []
				h3 = []
				for attr in node_columns:
					h1.append("node1:{}".format(attr))
					h2.append("node2:{}".format(attr))
				for attr in edge_columns:
					h3.append(attr)
				csvw.writerow(h1 + h2 + h3)

			# export data
			for n1, n2, e in graph.edges_iter(data=True):
				curr_node1_cells	= self._aggregateCsvCells(graph.node[n1], node_columns, flatten, skip_none_lines)
				curr_node2_cells	= self._aggregateCsvCells(graph.node[n2], node_columns, flatten, skip_none_lines)
				curr_edge_cells	= self._aggregateCsvCells(e, edge_columns, flatten, skip_none_lines)

				if curr_node1_cells and curr_node2_cells and curr_edge_cells:
					csvw.writerow(curr_node1_cells + curr_node2_cells + curr_edge_cells)
					row_count += 1
				else:
					empty_row_count += 1

		self.logging.debug("exportNetworkToCsv() returns with {} rows + header, {} empty rows have been thrown away".format(row_count, empty_row_count))


	def exportNodesToCsv(self, graph, filename, node_columns, header = tuple(), flatten = True, skip_none_lines = True):
		self.logging.info("""exportNodesToCsv() started,
			filename: {}
			header: {}
			node_columns: {}
			flatten: {}
			skip_none_lines: {}
		""".format(filename, header, node_columns, flatten, skip_none_lines))

		if header and len(header) != len(node_columns):
			raise ValueError("exportNetworkToCsv(): length of header is not the same as length of node columns!")

		row_count = 0
		empty_row_count = 0
		with gzip.open(filename, 'w') as fp:
			csvw = csv.writer(
				io.TextIOWrapper(fp, newline="", write_through=True), # text into binary file
				delimiter="\t",
				quoting=csv.QUOTE_MINIMAL
			)

			# header
			if header:
				csvw.writerow([attr for attr in header])
			else:
				csvw.writerow([attr for attr in node_columns])

			# export data
			for n, d in graph.nodes_iter(data=True):
				curr_row = self._aggregateCsvCells(d, node_columns, flatten, skip_none_lines)
				if curr_row:
					csvw.writerow(curr_row)
					row_count += 1
				else:
					empty_row_count += 1

		self.logging.debug("exportNodesToCsv() returns with {} rows + header, {} empty rows have been thrown away".format(row_count, empty_row_count))


	def _aggregateCsvCells(self, data, cells, flatten = True, skip_none_lines = True):
		row = []
		skip_curr_line = False

		for cell_name in cells:
			d = data[cell_name] # throws KeyError: bad column name if node attribute is not found
			flat_d = []
			
			if flatten and isinstance(d, (list, tuple)):
				flat_d = '|'.join( self._flattenList(d, skip_none_lines) )
			elif flatten and isinstance(d, dict):
				flat_d = '|'.join( self._flattenDict(d, skip_none_lines) )
			else:
				flat_d = d
			
			# if any of the cells contains none, skip (if Nones should be skipped)
			if not flat_d and skip_none_lines:
				return []
			else:
				row.append(flat_d)

		return row


	def _flattenList(self, data, skip_none_lines = True):
		""" Recursive flattening of lists and tuples. """
		d = []

		for i in data:
			if i is None and skip_none_lines:
				return []
			elif isinstance(i, list):
				d.extend(self._flattenList(i, skip_none_lines))
			elif isinstance(i, dict):
				d.extend(self._flattenDict(i, skip_none_lines))
			else:
				d.append(str(i))
				
		return d

	
	def _flattenDict(self, data, skip_none_lines = True):
		""" Recursive flattening of dicts. """
		d = []

		for k, v in data.items():
			if v is None and skip_none_lines:
				return []
			elif isinstance(v, list):
				d.extend(self._flattenList(v, skip_none_lines))
			elif isinstance(v, dict):
				d.extend(self._flattenDict(v, skip_none_lines))
			else:
				d.append(str(k)+':'+str(v))
				
		return d


if __name__ == '__main__':
	main_parser = argparse.ArgumentParser(description="Build the global ComPPI network and export various subnetworks of it.")
	main_parser.add_argument(
		'mode',
		choices=['build', 'export', 'egograph'],
		help="'build': (Re-)Build the global ComPPI network from the database and refresh the cache.\n'export': Export a subnetwork. If there is no global ComPPI network, it is automatically built.")
	main_parser.add_argument(
		'-t',
		'--type',
		choices=['proteinloc', 'compartment', 'interaction', 'all'],
		help="Determines the type of the exported subnetwork.")
	main_parser.add_argument(
		'-s',
		'--species',
		choices=['hsapiens', 'dmelanogaster', 'celegans', 'scerevisiae', 'all'],
		help="Species.")
	main_parser.add_argument(
		'-l',
		'--loc',
		choices=['cytoplasm', 'extracellular', 'mitochondrion', 'secretory-pathway', 'nucleus', 'membrane', 'all'],
		help="Major localization.")
	main_parser.add_argument(
		'-n',
		'--node_id',
		type=int,
		help="Node ID for generating egographs.")

	args = main_parser.parse_args()

	if args.mode=='build':
		c = ComppiInterface()
		c.cache_enabled = False
		c.buildGlobalComppi()
	elif args.mode=='egograph':
		c = ComppiInterface()
		comppi = c.buildGlobalComppi()
		egograph = c.buildEgoGraph(comppi, args.node_id)
		#print(egograph.edges(data=True))
		nx.write_gml(egograph, 'egograf.gml')
		#nx.write_gexf(egograph, 'egograf.gexf')
		#nx.write_graphml(egograph, 'egograf.graphml')
	elif args.mode=='export':
		c = ComppiInterface()
		all_specii = c.specii_opts
		all_locs = c.loc_opts
		del c

		#if not args.hasattr('type'):
		#	raise ValueError("--type/-t must be specified in export mode!")
		#if not args.hasattr('species'):
		#	raise ValueError("--species/-s must be specified in export mode!")
		#if not args.hasattr('loc'):
		#	raise ValueError("--loc/-l must be specified in export mode!")

		if args.species != 'all' and args.species in all_specii:
			all_specii = {args.species: all_specii[args.species]}
		if args.loc != 'all' and args.loc in all_locs:
			all_locs = [args.loc]

		for sp, sp_id in all_specii.items():
			for loc in all_locs:
				# the script is much faster if ComppiInterface is always re-created and destroyed
				# (the reason may be the garbage collection?)
				ci = ComppiInterface()
				# it is much faster to always reload the whole graph from cache
				# instead of deep-copying it in filterGraph
				# it must be reloaded, otherwise wrong filtered graph will be re-used!
				comppi = ci.buildGlobalComppi()

				# the original graph is always re-loaded, because
				# the graph filtering is done in-place (orig. graph is overwritten) to fit into lower RAM
				comppi = ci.filterGraph(comppi, loc, sp_id) # note the sp_id

				# various types of networks
				if args.type=='proteinloc' or args.type=='all':
					print("Type 'proteinloc', loc '{}', tax '{}' started... ".format(loc, sp), end="")
					
					ci.exportNodesToCsv(
						comppi,
						os.path.join(ci.output_dir, 'comppi--proteins_locs--tax_{}_loc_{}.txt.gz'.format(sp, loc)),
						('name', 'naming_conv', 'synonyms', 'loc_scores', 'minor_locs', 'loc_exp_sys', 'loc_source_dbs', 'loc_pubmed_ids', 'taxonomy_id'),
						('Protein Name', 'Naming Convention', 'Synonyms', 'Major Loc With Loc Score', 'Minor Loc', 'Experimental System Type', 'Localization Source Database', 'PubmedID', 'TaxID'),
						skip_none_lines = False
					)
					
					print("[ OK ]")

				if args.type=='compartment' or args.type=='all':
					print("Type 'compartment', loc '{}', tax '{}' started... ".format(loc, sp), end="")
					
					# all locs for compartments == all proteins that belong to all compartments
					# some proteins may not have localization data
					# -> all locs for compartments is not the same as all locs for interaction
					ci.exportCompartmentToCsv(
						comppi,
						os.path.join(ci.output_dir, 'comppi--compartments--tax_{}_loc_{}.txt.gz'.format(sp, loc)),
						('name', 'naming_conv', 'loc_scores', 'minor_locs', 'loc_exp_sys', 'loc_source_dbs', 'loc_pubmed_ids', 'taxonomy_id'),
						('weight', 'int_exp_sys', 'source_dbs', 'pubmed_ids'),
						('Interactor A', 'Naming Convention A', 'Major Loc A With Loc Score', 'Minor Loc A', 'Loc Experimental System Type A', 'Loc Source DB A', 'Loc PubMed ID A', 'Taxonomy ID A',
						'Interactor B', 'Naming Convention B', 'Major Loc B With Loc Score', 'Minor Loc B', 'Loc Experimental System Type B', 'Loc Source DB B', 'Loc PubMed ID B', 'Taxonomy ID B',
						'Interaction Score', 'Interaction Experimental System Type', 'Interaction Source Database', 'Interaction PubMed ID'
						),
						skip_none_lines = False
					)
					
					print("[ OK ]")

				if args.type=='interaction' or args.type=='all':
					print("Type 'interaction', loc '{}', tax '{}' started... ".format(loc, sp), end="")
					
					# "all locs" for interactions == filtering by localization is completely turned off
					if loc=='all':
						# the comppi graph is already filtered in_place,
						# therefore we have to reload the global graph to avoid filtering
						out_graph = ci.buildGlobalComppi()
						ci.logging.info("Interactions, loc 'all' => Unfiltered (original) graph is used for interactions instead of the last logged filtered one.")
					else:
						out_graph = comppi # already filtered instance
					
					ci.exportNetworkToCsv(
						out_graph,
						os.path.join(ci.output_dir, 'comppi--interactions--tax_{}_loc_{}.txt.gz'.format(sp, loc)),
						('name', 'naming_conv', 'synonyms', 'taxonomy_id'),
						('weight', 'int_exp_sys', 'source_dbs', 'pubmed_ids'),
						('Protein A', 'Naming Convention A', 'Synonyms A', 'Taxonomy ID A', 'Protein B', 'Naming Convention B', 'Synonyms B', 'Taxonomy ID B', 'Interaction Score', 'Interaction Experimental System Type', 'Interaction Source Database', 'Interaction PubMed ID'),
						skip_none_lines = False
					)
					
					print("[ OK ]")

				del ci

	else:
		raise ValueError("'build' or 'export' must be the first command line parameter.")