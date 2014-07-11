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
main_parser.add_argument(
	'-r',
	'--radius',
	type=int,
	required=True,
	help="The radius as an integer, a.k.a. the number of steps into the shady neighborhood.")
main_parser.add_argument(
	'-l',
	'--loc',
	required=True,
	help="The name of the major localization.")
args = main_parser.parse_args()

c = ComppiInterface()

if args.loc not in c.locs:
	raise ValueError("Unknown localization!")

print("Building ComPPI, see the log for details...")
comppi = c.buildGlobalComppi()

print("Building the egograph...")
egograph = c.buildEgoGraph(comppi, args.node_id, args.radius)
print("Egograph: {} nodes, {} edges".format(egograph.number_of_nodes(), egograph.number_of_edges()))

print("Exporting to CSV...")
with open("egograph-n_{}-r_{}.csv".format(args.node_id, args.radius), "w") as fp:
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

print("Filtering the egograph...")
filtered_egograph = c.filterGraph(egograph, args.loc, 0) # graph, loc, species
print("Filtered Egograph: {} nodes, {} edges".format(
	filtered_egograph.number_of_nodes(),
	filtered_egograph.number_of_edges()
))

with open("egograph-filtered-n_{}-r_{}-l_{}.csv".format(args.node_id, args.radius, args.loc), "w") as fp:
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
	for n1, n2, e in filtered_egograph.edges_iter(data=True):
		# note: same cells as in header
		csvw.writerow([
			n1,
			filtered_egograph.node[n1]['name'],
			n2,
			filtered_egograph.node[n2]['name'],
			e['weight']
		])

print("[ OK ]")