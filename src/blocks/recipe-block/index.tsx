import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import metadata from './block.json';

registerBlockType(metadata.name, {
	edit: () => {
		const blockProps = useBlockProps();
		return (
			<div {...blockProps}>
				<p>This is the Recipe Block. Customize it as needed.</p>
			</div>
		);
	},
	save: () => {
		const blockProps = useBlockProps.save();
		return (
			<div {...blockProps}>
				<p>This is the saved content of the Recipe Block.</p>
			</div>
		);
	},
});
