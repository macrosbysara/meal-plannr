import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => {
		const blockProps = useBlockProps();
		const innerBlocksProps = useInnerBlocksProps( blockProps, {
			allowedBlocks: [ 'meal-plannr/recipe-ingredients-block' ],
			template: [ [ 'meal-plannr/recipe-ingredients-block' ] ],
		} );
		return <ul { ...innerBlocksProps } />;
	},
	save: () => {
		const blockProps = useBlockProps.save();
		const innerBlocksProps = useInnerBlocksProps.save( blockProps );
		return <ul { ...innerBlocksProps } />;
	},
} );
