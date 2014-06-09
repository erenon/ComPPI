"""
sudo apt-get install python3-mysql.connector python3-pip
sudo pip3 install networkx
sudo pip3 install numpy

sp x loc all csak header ak?r prot, ak?r int
"""

#!/usr/bin/python3
import argparse
import logging
import os
import configparser
# python3-mysql.connector: http://dev.mysql.com/doc/connector-python/en/index.html
import mysql.connector
import csv

class ComppiInterface(object):
	"""
	Class to get and export data from the ComPPI database.

	attrib cfg_file: str, the parameters.ini file of Symphony to share the DB settings
	"""

	cfg_file 	= os.path.join('..', 'app', 'config', 'parameters.ini')
	log_file	= os.path.join('..', 'web', 'download', 'export_downloads.log')
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
	spec_abbr	= {
		0 : 'hsapiens', # H. sapiens
		1 : 'dmelanogaster', # D. melanogaster
		2 : 'celegans', # C. elegans
		3 : 'scerevisiae' # S. cerevisiae
	}
	locs 		= ['cytoplasm', 'extracellular', 'mitochondrion', 'secretory-pathway', 'nucleus', 'membrane']


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


	def exportNodesToCsv(self, sp=-1, loc=''):
		self.log.info("exportNodesToCsv(), sp: '{}', loc: '{}'".format(sp, loc))
		# @TODO: Syn (UniProt Full / first mapping)		Localization Score
		nodes_iter	= self.loadActors(sp)
		locs		= self.loadLocalizations(loc)

		if sp not in self.specii:
			sp = 'all'
		if loc not in self.locs:
			loc = 'all'
		out_f = os.path.join(self.output_dir, 'comppi--proteins_localizations-sp_{}-loc_{}.txt'.format(sp, loc))

		num_rows = 0
		with open(out_f, 'w', newline='') as fp:
			csvw = csv.writer(fp, delimiter="\t", quoting=csv.QUOTE_MINIMAL)
			# header
			csvw.writerow([
				'Protein Name',
				'Naming Convention',
				'Major Loc',
				'Minor Loc',
				'Experimental System Type',
				'Localization Source Database',
				'PubmedID',
				'TaxID'
			])
			# data
			for actor_id, sp, prot_name, naming_conv in nodes_iter:
				num_rows += 1
				ld = locs.get(actor_id) # loc data aggregated per protein
				if ld is not None and ('deleted' not in naming_conv.lower()):
					csvw.writerow([
						prot_name,
						naming_conv,
						','.join(ld.get('loc_majors', [])),
						','.join(ld.get('loc_minors', [])),
						','.join(ld.get('loc_exp_sys', [])),
						','.join(ld.get('loc_source_dbs', [])),
						','.join(ld.get('loc_pubmeds', [])),
						self.specii.get(sp, '')
					])

		self.log.info("exportNodesToCsv(), {} rows (+header) written to '{}'".format(num_rows, out_f))


	def exportEdgesToCsv(self, sp=-1, loc=''):
		# @TODO: Syn A (UniProt Full / first mapping)	Syn B (UniProt Full / first mapping)	Interaction Score

		self.log.info("exportEdgesToCsv(), sp: '{}', loc: '{}'".format(sp, loc))

		filtered_prot_ids			= []
		filter_by_locs				= False
		nodes_iter				= self.loadActors(sp)
		edges_iter				= self.loadInteractionsWithExpSysType()

		if sp not in self.specii:
			sp = 'all'

		# if localization is specified, get the protein IDs only from that loc
		if loc not in self.locs:
			loc = 'all'
		else:
			filter_by_locs = True
			filtered_prot_ids = self.getProteinIdsByMajorLoc(loc)

		# fetch protein names and taxonomy IDs
		proteins = {}
		for pid, species, name, naming_conv in nodes_iter:
			if not filter_by_locs or (filter_by_locs and pid in filtered_prot_ids):
				proteins[pid] = ( name, naming_conv, self.specii.get(int(species)) )

		# export the edges
		out_f = os.path.join(self.output_dir, 'comppi--interactions-sp_{}-loc_{}.txt'.format(sp, loc))
		num_rows = 0
		with open(out_f, 'w', newline='') as fp:
			csvw = csv.writer(fp, delimiter="\t", quoting=csv.QUOTE_MINIMAL)
			# header
			csvw.writerow([
				'Interactor A',
				'Interactor B',
				'Naming Convention A',
				'Naming Convention B',
				'Interaction Experimental System Type',
				'Interaction Source Database',
				'PubmedID',
				'TaxID'
			])
			# data
			for int_id, actor_a_id, actor_b_id, source_db, pubmed_id, exp_sys_type in edges_iter:
				actor_a = proteins.get(actor_a_id)
				actor_b = proteins.get(actor_b_id)
				# sort out: rows where any naming convention is "UniProtKB/deleted" or localization is 'N/A'
				if actor_a is not None and actor_b is not None and ('deleted' not in actor_a[1].lower() and 'deleted' not in actor_b[1].lower()) and ('n/a' not in actor_a[2].lower() or 'n/a' not in actor_b[2].lower()):
					num_rows += 1
					csvw.writerow([
						actor_a[0], # protein name for actor A
						actor_b[0], # protein name for actor B
						actor_a[1], # naming convention for actor A
						actor_b[1], # naming convention for actor B
						exp_sys_type,
						#int_exp_sys_types.get(int_id),
						source_db,
						pubmed_id,
						# it is assumed that both interactors are in the same species
						# -> species of interactor A is used
						actor_a[2]
					])

		self.log.info("exportEdgesToCsv(), {} rows (+header) written to '{}'".format(num_rows, out_f))


	def loadInteractions(self):
		cur = self.connect()
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


	def loadActors(self, sp=-1):
		""" Load the list of interactors (proteins).

			If the species is not defined (or not found in the predefined species pool), all proteins will be returned.

			param sp: int, optional, the ID of the species. It is not a taxonomy ID, rather an integer between 0 and 3. See self.specii for further information.

			returns: python-mysql.connector cursor object (iterable).
		"""
		self.log.debug("loadActors() started")
		cur = self.connect()
		if sp in self.specii:
			sql = """
				SELECT id, specieId, proteinName, proteinNamingConvention
				FROM Protein
				WHERE specieId = %s
			"""
			cur.execute(sql, (sp,))
		else:
			sql = """
				SELECT id, specieId, proteinName, proteinNamingConvention
				FROM Protein
			"""
			cur.execute(sql)
		self.log.debug("loadActors():\n{}".format(cur.statement))

		self.log.info("loadActors() returning with {} rows""".format(cur.rowcount))
		return cur


	def loadSynonyms(self, ):
		pass


	def loadLocalizations(self, loc):
		self.log.debug("loadLocalizations() started")

		cur = self.connect()
		if loc in self.locs:
			sql = """
				SELECT DISTINCT
					ptl.proteinId as pid, ptl.sourceDb, ptl.pubmedId,
					lt.name as minorLocName, lt.goCode as minorLocGo, lt.majorLocName,
					st.name AS exp_sys, st.confidenceType AS exp_sys_type
				FROM ProtLocToSystemType pltst, SystemType st, ProteinToLocalization ptl
				LEFT JOIN Loctree lt ON ptl.localizationId=lt.id
				WHERE
					ptl.id=pltst.protLocId AND
					pltst.systemTypeId=st.id AND
					lt.majorLocName = %s
			"""
			cur.execute(sql, (loc,))
		else:
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
			cur.execute(sql)


		self.log.debug("loadLocalizations():\n{}".format(cur.statement))

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


	def getProteinIdsByMajorLoc(self, loc):
		cur = self.connect()
		sql = """
			SELECT DISTINCT ptl.proteinId
			FROM ProteinToLocalization ptl
			LEFT JOIN Loctree lt ON ptl.localizationId=lt.id
			WHERE
				lt.majorLocName = %s
		"""
		cur.execute(sql, (loc,))

		return [l[0] for l in cur]


if __name__ == '__main__':
	parser = argparse.ArgumentParser()
	parser.add_argument(
		'-a', '--auto', required=True, help="Automatic mode: 1 (on) or 0 (off), decides if all combinations are generated automatically.")
	parser.add_argument(
		'-t', '--type', required=True, help="Type: 'interactions' or 'proteins'. Only if auto==0.")
	parser.add_argument(
		'-s',
		'--species',
		choices=['0', '1', '2', '3', 'all'],
		required=True, help="Species, [0-4] or 'all'. Only if auto==0.")
	parser.add_argument(
		'-l',
		'--loc',
		required=True,
		choices=['all', 'cytoplasm', 'extracellular', 'mitochondrion', 'secretory-pathway', 'nucleus', 'membrane'],
		help="Major loc: 'cytoplasm', 'extracellular', 'mitochondrion', 'secretory-pathway', 'nucleus', 'membrane' or 'all'. Only if auto==0.")
	args = parser.parse_args()

	if int(args.auto) == 1:
		c = ComppiInterface()
		all_specii = c.specii
		all_locs = c.locs
		del c

		all_specii['all'] = 'all'
		all_locs.append('all')

		for sp in all_specii:
			for loc in all_locs:
				ci = ComppiInterface()
				ci.exportNodesToCsv(sp, loc)
				ci.exportEdgesToCsv(sp, loc)
				del ci
	else:
		c = ComppiInterface()

		if args.species == 'all':
			sp = 'all'
		elif int(args.species) in c.specii:
			sp = int(args.species)
		else:
			raise InputError("Invalid species argument!")

		if args.loc == 'all' or args.loc in c.locs:
			loc = args.loc
		elif int(args.loc) in c.locs:
			loc=int(args.loc)
		else:
			raise InputError("Invalid major loc argument!")

		if args.type == 'interactions':
			c.exportEdgesToCsv(sp, loc)
		elif args.type == 'proteins':
			c.exportNodesToCsv(sp, loc)
		else:
			raise InputError("Invalid type argument!")