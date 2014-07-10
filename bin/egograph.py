#!/usr/bin/python3
""" ComPPI egograph script for analyses.
	Use from the directory where 'export_downloads.py' sits.
"""

import csv
import argparse
from export_downloads import ComppiInterface

main_parser = argparse.ArgumentParser(description="Create egographs of nodes from the ComPPI network.")
main_parser.add_argument(
	'-n',
	'--node_id',
	type=int,
	required=True,
	help="The node ID (= ComPPI ID) of a protein as an integer.")
args = main_parser.parse_args()

c = ComppiInterface()

print("Building ComPPI, see the log for details...")
comppi = c.buildGlobalComppi()

print("Building the egograph...")
egograph = c.buildEgoGraph(comppi, args.node_id)

print("Exporting to CSV...")
with open("egograph.csv", "w") as fp:
	csvw = csv.writer(fp, delimiter="\t", quoting=csv.QUOTE_MINIMAL)
	
	# header
	csvw.writerow([
		'node_a_id',
		'node_a_name',
		'node_b_id',
		'node_b_name',
		'int_score'
	])
	
	# interactions
	for n1, n2, e in egograph.edges_iter(data=True):
		# note: same cells as in header
		csvw.writerow([
			n1,
			egograph.node[n1]['name'],
			n2,
			egograph.node[n2]['name'],
			e['weight']
		])

print("[ OK ]")