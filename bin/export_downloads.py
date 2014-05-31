"""
sudo apt-get install python3-mysql.connector python3-pip
sudo pip3 install networkx
sudo pip3 install numpy
"""

#!/usr/bin/python3
import logging
import os
import configparser
# python3-mysql.connector: http://dev.mysql.com/doc/connector-python/en/index.html
import mysql.connector
import csv

import pprint
import sys

class ComppiInterface(object):
	"""
	Class to get and export data from the ComPPI database.
	
	attrib cfg_file: str, the parameters.ini file of Symphony to share the DB settings
	"""

	cfg_file 	= os.path.join('..', 'app', 'config', 'parameters.ini')
	log_file	= os.path.join('..', 'web', 'export_downloads.log')
	output_dir 	= os.path.join('..', 'web', 'download')
	db_conn		= None
	cursor		= None
	db_name 	= ''
	db_host 	= ''
	db_user 	= ''
	db_pwd  	= ''
	specii		= {
		0 : '9606', # H. sapiens
		1 : '7227', # D. melanogaster
		2 : '6239', # C. elegans
		3 : '4932' # S. cerevisiae
	}
	locs 		= frozenset(['cytoplasm', 'extracellular', 'mitochondrion', 'secretory-pathway', 'nucleus', 'membrane'])


	def __init__(self):
		logging.basicConfig(
				filename = self.log_file,
				filemode = 'w', # a for append, w for overwrite
				format = '%(asctime)s - %(levelname)s - %(message)s',
				level = logging.DEBUG)
		
		self.log = logging
		
		cfg = configparser.ConfigParser()
		cfg.read(self.cfg_file)
		
		self.log.info("Config loaded")
		
		self.db_name	= cfg['parameters']['database_name']
		self.db_host 	= cfg['parameters']['database_host']
		self.db_user 	= cfg['parameters']['database_user']
		self.db_pwd  	= cfg['parameters']['database_password']


	def connect(self):
		if self.db_conn is None:
			self.log.info("Connecting to database...")
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
			self.log.info("Database connection established")
		
		# returning a new cursor object every time prevents overwrite of cursor buffer
		return self.db_conn.cursor(buffered=True)


	#def assembleAll(self, sp, loc):
	#	nodes_iter	= self.loadActors(sp)
	#	edges_iter	= self.loadInteractions()
	#	locs		= self.loadLocalizations(loc)
	#	
	#	nodes = {}
	#	for actor_id, sp, name in nodes_iter:
	#		node_loc_data = locs.get(actor_id)
	#		if node_loc_data is not None:
	#			nodes[actor_id] 			= node_loc_data.copy()
	#			nodes[actor_id]['name'] 	= name
	#			nodes[actor_id]['species'] 	= species
	#	
	#	edges = {}
	#	for actor_a, actor_b, source_db, pubmed_id in edges_iter:
	#		if actor_a in nodes or actor_b in nodes:
	#			ed = edges.setdefault((actor_a, actor_b), {'source_dbs': [], 'pubmeds': []})
	#			ed['source_dbs'].append(source_db)
	#			ed['pubmeds'].append(pubmed_id)
	#	
	#	return (nodes, edges)


	def exportNodesToCsv(self, sp, loc):
		self.log.info("exportNodesToCsv(), sp: '{}', loc: '{}'".format(sp, loc))
		# @TODO: Syn (UniProt Full / first mapping)		Localization Score
		nodes_iter	= self.loadActors(sp)
		locs		= self.loadLocalizations(loc)
		
		out_f = os.path.join(self.output_dir, 'proteins_localizations.csv')
		num_rows = 0
		with open(out_f, 'w', newline='') as fp:
			csvw = csv.writer(fp, delimiter="\t", quoting=csv.QUOTE_MINIMAL)
			# header
			csvw.writerow([
				'Protein Name',
				'Major Loc',
				'Minor Loc',
				'Experimental System Type',
				'Localization Source Database',
				'PubmedID',
				'TaxID'
			])
			# data
			for actor_id, sp, prot_name in nodes_iter:
				num_rows += 1
				ld = locs.get(actor_id, {}) # loc data aggregated per protein
				csvw.writerow([
					prot_name,
					','.join(ld.get('loc_majors', [])),
					','.join(ld.get('loc_minors', [])),
					','.join(ld.get('loc_exp_sys', [])),
					','.join(ld.get('loc_source_dbs', [])),
					','.join(ld.get('loc_pubmeds', [])),
					self.specii.get(sp, '')
				])
			
		self.log.info("exportNodesToCsv(), {} rows (+header) written to '{}'".format(num_rows, out_f))
	

	def exportEdgesToCsv(self, sp):
		# @TODO: Syn A (UniProt Full / first mapping)	Syn B (UniProt Full / first mapping)	Interaction Score
		nodes_iter			= self.loadActors(sp)
		#edges_iter			= self.loadInteractions()
		#int_exp_sys_types	= self.loadInteractionExpSysTypes()
		edges_iter			= self.loadInteractionsWithExpSysType()
		#locs				= self.loadLocalizations(loc)
		
		# extract protein names as: {comppi_id: (protein name, species)}
		proteins = {}
		tax_ids = {}
		for pid, sp, name in nodes_iter:
			proteins[pid] = name
			tax_ids[pid] = self.specii.get(int(sp))
		
		out_f = os.path.join(self.output_dir, 'interactions.csv')
		num_rows = 0
		with open(out_f, 'w', newline='') as fp:
			csvw = csv.writer(fp, delimiter="\t", quoting=csv.QUOTE_MINIMAL)
			# header
			csvw.writerow([
				'Interactor A',
				'Interactor B',
				'Interaction Experimental System Type',
				'Interaction Source Database',
				'PubmedID',
				#'TaxID'
			])
			# data
			for int_id, actor_a_id, actor_b_id, source_db, pubmed_id, exp_sys_type in edges_iter:
				num_rows += 1
				csvw.writerow([
					proteins.get(actor_a_id),
					proteins.get(actor_b_id),
					exp_sys_type,
					#int_exp_sys_types.get(int_id),
					source_db,
					pubmed_id,
					# it is assumed that both interactors are in the same species
					# -> species of interactor A is used
					tax_ids.get(actor_a_id, 'N/A')
				])
			
		self.log.info("exportEdgesToCsv(), {} rows (+header) written to '{}'".format(num_rows, out_f))

	
	def loadInteractions(self):
		cur = self.connect()
		# selecting all the interactions and filtering them later is
		# much faster even if the memory footpring is larger
		# than pre-filtering them
		sql = "SELECT id, actorAId, actorBId, sourceDb, pubmedId FROM Interaction"
		self.log.debug(sql)
		cur.execute(sql)
		
		return cur
	
	def loadInteractionsWithExpSysType(self):
		sql = """
			SELECT
				i.id, i.actorAId, i.actorBId, i.sourceDb, i.pubmedId, st.name
			FROM
				Interaction i, InteractionToSystemType itst
			LEFT JOIN
				SystemType st ON itst.systemTypeId=st.id
			WHERE
				i.id=itst.interactionId
		"""
		
		self.log.debug("loadInteractionsWithExpSysType():{}".format(sql))
		cur = self.connect()
		cur.execute(sql)
		self.log.info("loadInteractionsWithExpSysType() returning with {} rows""".format(cur.rowcount))
	
		return cur
	#
	#
	#def loadInteractionExpSysTypes(self):
	#	sql = """
	#		SELECT DISTINCT interactionId, st.name
	#		FROM InteractionToSystemType itst
	#		LEFT JOIN SystemType st ON itst.systemTypeId=st.id
	#	"""
	#	
	#	self.log.debug("loadInteractionSysTypes():{}".format(sql))
	#	cur = self.connect()
	#	cur.execute(sql)
	#	self.log.info("loadInteractionSysTypes() returning with {} rows""".format(cur.rowcount))
	#	
	#	return dict(cur)


	def loadActors(self, sp=''):
		sql = "SELECT id, specieId, proteinName FROM Protein"
		if sp in self.specii:
			sql += " WHERE specieId=" + str(sp)
		
		self.log.debug("loadActors():\n{}".format(sql))
		cur = self.connect()
		cur.execute(sql)
		self.log.info("loadActors() returning with {} rows""".format(cur.rowcount))
		
		return cur
	

	def loadSynonyms(self, ):
		pass


	def loadLocalizations(self, loc):
		sql = """
			SELECT DISTINCT
				ptl.proteinId as pid, ptl.sourceDb, ptl.pubmedId,
				lt.name as minorLocName, lt.goCode as minorLocGo, lt.majorLocName,
				st.name AS exp_sys, st.confidenceType AS exp_sys_type
			FROM ProtLocToSystemType pltst, SystemType st, ProteinToLocalization ptl
			LEFT JOIN Loctree lt ON ptl.localizationId=lt.id
			WHERE
				ptl.id=pltst.protLocId AND
				pltst.systemTypeId=st.id
		"""
		
		if loc in self.locs:
			sql += " AND lt.majorLocName='%s'".format(loc)
		
		self.log.debug("loadLocalizations():\n{}".format(sql))
		cur = self.connect()
		cur.execute(sql)
		
		# make a dictionary of it for fast access
		# there can be multiple localizations for a single protein
		# -> aggregate per comppi ID
		d = {}
		for row in cur:
			# protein ID (comppi ID) as main dict key
			currd = d.setdefault(row[0], {})
			# source DB of protein -> localization mapping
			locdb = currd.setdefault('loc_source_dbs', [])
			locdb.append(row[1])
			# publication reference of protein -> localization mapping
			locpm = currd.setdefault('loc_pubmeds', [])
			locpm.append(str(row[2]))
			# minor locations as GO IDs
			locmin = currd.setdefault('loc_minors', [])
			locmin.append(row[4])
			# major locations as strings
			locmaj = currd.setdefault('loc_majors', [])
			locmaj.append(row[5])
			# experimental system
			locexps = currd.setdefault('loc_exp_sys', [])
			locexps.append(row[6])
	
		self.log.info("loadLocalizations(), returning with {} localizations".format(len(d)))
		return d


if __name__ == '__main__':
	c = ComppiInterface()
	#c.exportNodesToCsv('', '')
	c.exportEdgesToCsv('')